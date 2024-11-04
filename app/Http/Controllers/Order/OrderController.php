<?php
namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\VnpayTransaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{

    private $orderId;
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

                // Lưu đơn hàng vào database trước
                $order = DB::transaction(function () use ($user, $defaultAddress, $paymentMethod, $totalAmount, $dataItem) {
                    Log::info('Lưu order vào database');

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

                    return $order;
                });

                // Gán ID đơn hàng vào biến
                $this->orderId = $order->id;

                // Gọi hàm xử lý thanh toán VNPAY sau khi đã lưu đơn hàng
                $vnpayResponse = $this->processVNPayment($totalAmount);

                if (!$vnpayResponse['success']) {
                    Log::error("Thanh toán thất bại");
                    return response()->json([
                        'success' => false,
                        'message' => 'Thanh toán VNPAY thất bại.'
                    ], 400);
                }

                Log::info("Chuyển hướng thanh toán");

                return response()->json([
                    'success' => true,
                    'message' => 'Đặt hàng thành công, chờ thanh toán.',
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
                    Log::info('Đặt hàng bằng tiền mặt thành công -> xoá giỏ hàng.');
                    Cart::where('user_id', $user->id)->delete();
                    return $order;
                });

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
        try {
            // Lấy đơn hàng mới nhất
            $latestOrder = Order::find($this->orderId);
            if (!$latestOrder) {
                Log::error('Không tìm thấy đơn hàng', ['order_id' => $this->orderId]);
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng'
                ];
            }

            // Các thông số cấu hình VNPAY
            $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
            $vnp_TmnCode = env('VNP_TMN_CODE');
            $vnp_HashSecret = env('VNP_HASH_SECRET');
            $vnp_Returnurl = route('vnpay.return');

            // Kiểm tra các thông số bắt buộc
            if (!$vnp_TmnCode || !$vnp_HashSecret) {
                Log::error('Thiếu thông tin cấu hình VNPAY');
                return [
                    'success' => false,
                    'message' => 'Thiếu thông tin cấu hình thanh toán'
                ];
            }

            // Chuẩn bị dữ liệu thanh toán
            $vnp_Amount = (int)round($totalAmount * 100);
            $vnp_TxnRef = $latestOrder->id; // Mã đơn hàng
            $vnp_OrderInfo = 'Thanh toan don hang #' . $latestOrder->id;

            // Tạo mảng dữ liệu gửi đi
            $inputData = array(
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $vnp_TmnCode,
                "vnp_Amount" => $vnp_Amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => request()->ip(),
                "vnp_Locale" => "vn",
                "vnp_OrderInfo" => $vnp_OrderInfo,
                "vnp_OrderType" => "other",
                "vnp_ReturnUrl" => $vnp_Returnurl,
                "vnp_TxnRef" => $vnp_TxnRef,
                "vnp_ExpireDate" => date('YmdHis', strtotime('+15 minutes')),
            );

            // Thêm BankCode nếu cần
            if (request()->has('bankcode')) {
                $inputData['vnp_BankCode'] = request('bankcode');
            }

            ksort($inputData);
            $query = "";
            $i = 0;
            $hashdata = "";

            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashdata .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
                $query .= urlencode($key) . "=" . urlencode($value) . '&';
            }

            // Tạo URL thanh toán
            $vnp_Url = $vnp_Url . "?" . $query;
            if (isset($vnp_HashSecret)) {
                $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
                $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
            }

            // Log thông tin để debug
            Log::info('VNPAY Payment Data', [
                'order_id' => $latestOrder->id,
                'amount' => $vnp_Amount,
                'input_data' => $inputData,
            ]);

            // Lưu thông tin giao dịch
            try {
                VnpayTransaction::create([
                    'order_id' => $latestOrder->id,
                    'transaction_id' => $vnp_TxnRef,
                    'amount' => $totalAmount,
                    'response_code' => null,
                    'secure_hash' => null,
                    'response_message' => null,
                ]);
            } catch (Exception $e) {
                Log::error('Lỗi khi lưu giao dịch VNPAY', [
                    'error' => $e->getMessage(),
                    'order_id' => $latestOrder->id
                ]);
            }

            return [
                'success' => true,
                'url' => $vnp_Url
            ];

        } catch (Exception $e) {
            Log::error('Lỗi xử lý thanh toán VNPAY', [
                'error' => $e->getMessage(),
                'order_id' => $this->orderId ?? null
            ]);

            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra trong quá trình xử lý thanh toán'
            ];
        }
    }

    public function paymentReturn(Request $request)
    {
        Log::info('Phản hồi từ VNPAY: ', $request->all());
        $vnp_HashSecret = env('VNP_HASHSECRET');
        $vnp_SecureHash = $request->vnp_SecureHash;
        $inputData = [];

        // Lấy dữ liệu từ phản hồi
        foreach ($request->all() as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }
        unset($inputData['vnp_SecureHashType'], $inputData['vnp_SecureHash']);

        // Sắp xếp và mã hóa dữ liệu
        ksort($inputData);
        $hashData = http_build_query($inputData);
        $secureHash = hash('sha256', $vnp_HashSecret . $hashData);

        // Tìm giao dịch VNPAY tương ứng
        $vnpayTransaction = VnpayTransaction::where('transaction_id', $request->vnp_TxnRef)->first();
        if ($vnpayTransaction) {
            $vnpayTransaction->update([
                'response_code' => $request->vnp_ResponseCode,
                'response_message' => $request->vnp_ResponseMessage,
                'secure_hash' => $vnp_SecureHash,
            ]);

            $order = Order::find($vnpayTransaction->order_id);
            $user = User::find($order->user_id);
            Log::info('User  ID: ' . ($user ? $user->id : 'Not authenticated'));

            if ($request->vnp_ResponseCode == '00') {
                // Xác nhận thanh toán thành công
                Log::info('Thanh toán thành công');

                // Gửi email xác nhận đơn hàng
//                if ($user) {
//                    try {
//                        Mail::to($user->email)->send(new OrderConfirmationMail($order->id));
//                        Log::info("Email xác nhận đã được gửi đến: {$user->email}");
//                    } catch (\Exception $e) {
//                        Log::error("Gửi email xác nhận thất bại: " . $e->getMessage());
//                    }
//                } else {
//                    Log::error("Người dùng không được tìm thấy.");
//                }

                // Cập nhật trạng thái đơn hàng
                $order->update([
                    'payment_status' => Order::STATUS_PAYMENT_PAID,
                ]);

                // Cập nhật số lượng sản phẩm
                foreach ($order->orderItems as $item) {
                    $product = $item->productVariant;

                    if ($product) {
                        if ($product->quantity >= $item->quantity) {
                            $product->decrement('quantity', $item->quantity);
                            Log::info(' Số lượng sản phẩm: ' . $product->quantity . ' - Số lần mua: ' . $item->quantity . '. Sản phầm còn lại là:' . ($product->quantity - $item->quantity));
                        } else {
                            Log::warning('Không đủ số lượng sản phẩm', [
                                'product_id' => $product->id,
                                'requested_quantity' => $item->quantity,
                                'available_quantity' => $product->quantity,
                            ]);
                        }
                    }
                }

                Log::info('Xóa giỏ hàng.');
                Cart::where('user_id', $order->user_id)->delete(); // Xóa giỏ hàng
                $orderItems = OrderItem::where('order_id', $order->id)->get();
                $productIds = $orderItems->pluck('product_id');

                $products = Product::whereIn('id', $productIds)->get();
                return response()->json([
                    'success' => true,
                    'message' => 'Thanh toán thành công',
                    'data' => [
                        'response_code' => $request->vnp_ResponseCode,
                        'user_id' => $user->id,
                        'order_id' => $order->id,
                        'total_amount' => $order->total_amount,
                        'note' => $order->note,
                        'order_item' => $orderItems,
                        'product' => $products,
                        'payment_status' => 'paid',
                        'payment_method' => 'Thanh toán online',

                    ]
                ]);
            } else {
                // Xóa đơn hàng và các mục đơn hàng nếu thanh toán thất bại
                if ($order) {
                    $order->orderItems()->delete(); // Xóa mục đơn hàng
                    $order->delete(); // Xóa đơn hàng

                    Log::warning('Đã xóa đơn hàng do thanh toán thất bại', [
                        'order_id' => $order->id,
                        'response_code' => $request->vnp_ResponseCode,
                        'input_data' => $inputData
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Thanh toán không thành công',
                    'error_code' => $request->vnp_ResponseCode,
                    'error_message' => $request->vnp_ResponseMessage
                ], 400);
            }
        } else {
            Log::error('Không tìm thấy giao dịch VNPAY để xác nhận thanh toán');
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy giao dịch VNPAY'
            ], 404);
        }
    }

}
