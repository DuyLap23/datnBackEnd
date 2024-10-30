<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\VnpayTransaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use function Laravel\Prompts\error;

class OrderController extends Controller
{
    public function order()
    {
        try {
            Log::info('Bắt đầu quy trình đặt hàng.');

            $user = auth('api')->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn cần đăng nhập để sử dụng tính năng này.'
                ], 401);
            }

            $cart = Cart::where('user_id', $user->id)->get();
            if ($cart->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Giỏ hàng không có sản phẩm nào.'
                ], 404);
            }

            $defaultAddress = $user->addresses()->where('is_default', true)->first();
            if (!$defaultAddress) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn cần thêm địa chỉ trước khi đặt hàng.'
                ], 400);
            }

            $paymentMethod = request('payment_method');
            $totalAmount = 0;
            $dataItem = [];

            foreach ($cart as $cartItem) {
                $totalAmount += $cartItem->quantity * ($cartItem->product->price_sale ?? $cartItem->product->price_regular);
                $dataItem[] = [
                    'product_id' => $cartItem->product_id,
                    'color' => $cartItem->color,
                    'size' => $cartItem->size,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->product->price_sale ?? $cartItem->product->price_regular,
                ];
            }

            // Kiểm tra phương thức thanh toán
            if ($paymentMethod == 1) {
                // Thanh toán qua VNPAY
                Log::info('Bắt đầu thanh toán qua VNPAY.');
                $vnpayResponse = $this->processVNPayment($totalAmount);

                if (!$vnpayResponse['success']) {
                    Log:
                    error(" Thanh toán thất bại");
                    return response()->json([
                        'success' => false,
                        'message' => 'Thanh toán VNPAY thất bại.'
                    ], 400);
                }
                Log::info("Thanh toán thành công");
                // Nếu thanh toán thành công
                $order = DB::transaction(function () use ($user, $defaultAddress, $paymentMethod, $totalAmount, $dataItem) {
                    $order = Order::create([
                        'user_id' => $user->id,
                        'address_id' => $defaultAddress->id,
                        'payment_method' => $paymentMethod,
                        'payment_status' => Order::STATUS_PAYMENT_PAID,
                        'order_status' => Order::STATUS_ORDER_PENDING,
                        'total_amount' => $totalAmount,
                        'note' => request('note'),
                    ]);

                    foreach ($dataItem as $item) {
                        $item['order_id'] = $order->id;
                        OrderItem::create($item);
                    }

//                    Cart::where('user_id', $user->id)->delete();
                    return $order;
                });

                Log::info("Đặt hàng thành công. $order->id");
                return response()->json([
                    'success' => true,
                    'message' => 'Đặt hàng thành công.',
                    'payment_url' => $vnpayResponse['url']
                ], 201);

            } elseif ($paymentMethod == 0) {
                // Thanh toán tiền mặt
                Log::info("Thanh toán bằng tiền mặt");
                $order = DB::transaction(function () use ($user, $defaultAddress, $paymentMethod, $totalAmount, $dataItem) {
                    $order = Order::create([
                        'user_id' => $user->id,
                        'address_id' => $defaultAddress->id,
                        'payment_method' => $paymentMethod,
                        'payment_status' => Order::STATUS_PAYMENT_UNPAID,
                        'order_status' => Order::STATUS_ORDER_PENDING,
                        'total_amount' => $totalAmount,
                        'note' => request('note'),
                    ]);

                    foreach ($dataItem as $item) {
                        $item['order_id'] = $order->id;
                        OrderItem::create($item);
                    }

//                    Cart::where('user_id', $user->id)->delete();
                    return $order;
                });
//                Mail::to($request->user()->email)->send(new OrderConfirmationMail($order));
                return response()->json([
                    'success' => true,
                    'message' => 'Đặt hàng thành công.'
                ], 201);

            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Phương thức thanh toán không hợp lệ.'
                ], 400);
            }

        } catch (Exception $e) {
            Log::error('Đặt hàng không thành công.', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Không thể đặt hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function processVNPayment(float $totalAmount)
    {
        // Lấy đơn hàng mới nhất
        $latestOrder = Order::latest()->first();
        if (!$latestOrder) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng'
            ];
        }

        // Các biến liên quan đến thanh toán VNPAY
        $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_Returnurl = route('vnpay.return');
        $vnp_TmnCode = "MNSW8HNE"; // Mã website tại VNPAY
        $vnp_HashSecret = "6K7T10YWUG5WS3NVOOQC3UOO8E9I0I0V"; // Chuỗi bí mật

        $vnp_TxnRef = $latestOrder->id; // Mã đơn hàng
        $vnp_OrderInfo = 'Thanh toán hoá đơn.';
        $vnp_OrderType = "Top Deal shop";
        $vnp_Amount = $totalAmount * 100;
        $vnp_Locale = "VN";
        $vnp_BankCode = "NCB";
        $vnp_IpAddr = request()->ip();

        Log::info('VNPAY URL: ' . $vnp_Url);
        // Tạo dữ liệu đầu vào cho VNPAY
        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
        ];

        if (!empty($vnp_BankCode)) {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }

        ksort($inputData);
        $query = "";
        $hashdata = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= '&vnp_SecureHash=' . $vnpSecureHash;
        }

        // Lưu giao dịch VNPAY vào cơ sở dữ liệu
        $vnpayTransaction = VnpayTransaction::create([
            'order_id' => $latestOrder->id,
            'transaction_id' => $vnp_TxnRef,
            'amount' => $vnp_Amount / 100, // Chuyển về đơn vị VND
            'response_code' => null,
            'response_message' => null,
            'secure_hash' => null,
        ]);

        return [
            'success' => true,
            'url' => $vnp_Url
        ];
    }

    public function paymentReturn(Request $request)
    {
        $vnp_HashSecret = env('VNP_HASHSECRET');
        $vnp_SecureHash = $request->vnp_SecureHash;
        $inputData = [];

        // Lấy dữ liệu từ phản hồi
        foreach ($request->all() as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }
        unset($inputData['vnp_SecureHashType']);
        unset($inputData['vnp_SecureHash']);

        // Sắp xếp dữ liệu
        ksort($inputData);
        $hashData = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . $key . "=" . $value;
            } else {
                $hashData .= $key . "=" . $value;
                $i = 1;
            }
        }

        // Tính toán mã bảo mật
        $secureHash = hash('sha256', $vnp_HashSecret . $hashData);

        // Tìm giao dịch VNPAY tương ứng
        $vnpayTransaction = VnpayTransaction::where('transaction_id', $request->vnp_TxnRef)->first();
        if ($vnpayTransaction) {
            $vnpayTransaction->response_code = $request->vnp_ResponseCode;
            $vnpayTransaction->response_message = $request->vnp_ResponseMessage;
            $vnpayTransaction->secure_hash = $vnp_SecureHash;
            $vnpayTransaction->save();

            Log::info("Cập nhật thông tin thanh toán VNPAY: " . $request->vnp_ResponseCode);

            if ($request->vnp_ResponseCode == '00') {
                // Cập nhật trạng thái đơn hàng thành "Đã thanh toán"
                $order = Order::find($vnpayTransaction->order_id);
                $order->payment_status = Order::STATUS_PAYMENT_PAID;
                $order->order_status = Order::STATUS_ORDER_PROCESSING;
                $order->save();

                // Cập nhật số lượng sản phẩm đã bán
                foreach ($order->orderItems as $item) {
                    $product = $item->product;
                    $product->quantity_sold += $item->quantity;
                    $product->save();
                }

                return response()->json(['success' => true, 'message' => 'Thanh toán thành công.']);
            } else {
                // Xử lý mã phản hồi khác
                Log::warning('Thanh toán thất bại', [
                    'response_code' => $request->vnp_ResponseCode,
                    'input_data' => $inputData
                ]);
                return response()->json(['success' => false, 'message' => 'Thanh toán thất bại.']);
            }
        } else {
            // Mã giao dịch không tồn tại
            Log::error('Mã giao dịch không tồn tại', [
                'input_data' => $inputData
            ]);
            return response()->json(['success' => false, 'message' => 'Thanh toán thất bại.']);
        }
    }
}
