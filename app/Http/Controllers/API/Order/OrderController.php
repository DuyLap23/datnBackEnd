<?php

namespace App\Http\Controllers\API\Order;

use App\Events\OrderSuccess;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\VnpayTransaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/orders",
     *     summary="Tạo đơn hàng mới",
     *     description="Tạo đơn hàng mới từ giỏ hàng của người dùng",
     *     tags={"Order"},
     *     security={{"Bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payment_method"},
     *             @OA\Property(
     *                 property="payment_method",
     *                 type="integer",
     *                 enum={0, 1},
     *                 example=1,
     *                 description="Phương thức thanh toán: 0 - Thanh toán khi nhận hàng, 1 - Thanh toán qua VNPAY"
     *             ),
     *             @OA\Property(
     *                 property="note",
     *                 type="string",
     *                 example="Giao hàng trong giờ hành chính",
     *                 description="Ghi chú cho đơn hàng"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Thanh toán thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đặt hàng thành công."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user_id", type="integer", example=3),
     *                 @OA\Property(property="order_id", type="integer", example=57),
     *                 @OA\Property(property="total_amount", type="string", example="1818744.00"),
     *                 @OA\Property(property="note", type="string", example="Giao nhanh cho anh"),
     *                 @OA\Property(
     *                     property="order_item",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=106),
     *                         @OA\Property(property="order_id", type="integer", example=57),
     *                         @OA\Property(property="quantity", type="integer", example=3),
     *                         @OA\Property(property="price", type="string", example="606248.00"),
     *                         @OA\Property(property="deleted_at", type="string", nullable=true, example=null),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-10-31T04:28:45.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-10-31T04:28:45.000000Z"),
     *                         @OA\Property(property="product_id", type="integer", example=17),
     *                         @OA\Property(property="color", type="string", example="#000000"),
     *                         @OA\Property(property="size", type="string", example="XXL")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="product",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=17),
     *                     @OA\Property(property="name", type="string", example="Quo placeat sed est est maiores. Id eos laborum rerum est harum qui sed. Quia autem autem vel rem."),
     *                     @OA\Property(property="slug", type="string", example="quo-placeat-sed-est-est-maiores-id-eos-laborum-rerum-est-harum-qui-sed-quia-autem-autem-vel-rem-IbiNWOYk"),
     *                     @OA\Property(property="sku", type="string", example="o6bbDUK16"),
     *                     @OA\Property(property="img_thumbnail", type="string", example="https://canifa.com/img/1000/1500/resize/8/b/8bj24s003-sj859-31-1-u.webp"),
     *                     @OA\Property(property="price_regular", type="string", example="739327.00"),
     *                     @OA\Property(property="price_sale", type="string", example="606248.00"),
     *                     @OA\Property(property="description", type="string", example="Sit harum aut tenetur minima. Qui iste molestias illo consequuntur optio dolorem. Adipisci dolorem porro expedita voluptatem non maiores nam."),
     *                     @OA\Property(property="content ", type="string", example="Tempora et velit culpa. Inventore quod enim ullam sequi vel natus dignissimos. Sed commodi unde qui dolores quia sint. Id aut est corrupti corporis vitae est est. Non qui laboriosam enim vitae repellendus. Perferendis rerum autem repudiandae veritatis quo..."),
     *                     @OA\Property(property="user_manual", type="string", example="Recusandae quia consequuntur at occaecati. Et enim veniam voluptatem in. Quam quia ut rem cupiditate quo. Quibusdam saepe enim quibusdam iure veritatis. Nobis qui rerum accusamus quas beatae ab cupiditate."),
     *                     @OA\Property(property="view", type="integer", example=0),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="is_new", type="boolean", example=true),
     *                     @OA\Property(property="is_show_home", type="boolean", example=true),
     *                     @OA\Property(property="category_id", type="integer", example=3),
     *                     @OA\Property(property="brand_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-10-21T06:30:18.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-10-21T06:30:18.000000Z"),
     *                     @OA\Property(property="deleted_at", type="string", nullable=true, example=null)
     *                 ),
     *                 @OA\Property(property="payment_status", type="string", example="paid"),
     *                 @OA\Property(property="payment_method", type="string", example="vnpay"),
     *                 @OA\Property(property="response_code", type="string", example="00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Lỗi validate dữ liệu hoặc chưa có địa chỉ mặc định",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bạn cần thêm địa chỉ trước khi đặt hàng.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Chưa đăng nhập",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bạn cần đăng nhập để sử dụng tính năng này.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Giỏ hàng trống hoặc không tìm thấy dữ liệu",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Giỏ hàng không có sản phẩm nào.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không thể đặt hàng"),
     *             @OA\Property(property="error", type="string", example="Internal Server Error")
     *         )
     *     )
     * )
     */
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
                        'order_code' => $this->generateOrderCode(),
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
                        'message' => 'Thanh toán VNPAY thất bại.' . $vnpayResponse['message']
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
                        'order_code' => $this->generateOrderCode(),
                        'address_id' => $defaultAddress->id,
                        'payment_method' => $paymentMethod,
                        'payment_status' => Order::STATUS_PAYMENT_UNPAID,
                        'order_status' => Order::STATUS_ORDER_PENDING,
                        'total_amount' => $totalAmount,
                        'note' => request('note'),
                    ]);
                    foreach ($dataItem as $item) {
                        $item['order_id'] = $order->id;
                        $quantityItems = $item['quantity'];
                        OrderItem::create($item);
                    }
                    Log::info('quantity: ' . $quantityItems);

                    foreach ($order->orderItems as $item) {

                        $product_id = $item->product_id;

                        $productVariant = ProductVariant::with(['productColor', 'productSize'])
                            ->where('product_id', $product_id)
                            ->whereHas('productColor', function ($query) use ($item) {
                                $query->where('name', $item->color);
                            })
                            ->whereHas('productSize', function ($query) use ($item) {
                                $query->where('name', $item->size);
                            })
                            ->first();

                        Log::info('Thông tin sản phẩm:', [
                            'product_id' => $product_id,
                            'color_name' => $item->color,
                            'size_name' => $item->size,
                            'product_variant' => $productVariant ? [
                                'id' => $productVariant->id,
                                'quantity' => $productVariant->quantity
                            ] : 'null'
                        ]);

                        if ($productVariant) {
                            if ($productVariant->quantity >= $item->quantity) {
                                // Trừ số lượng
                                $productVariant->decrement('quantity', $item->quantity);

                                Log::info('Cập nhật số lượng thành công', [
                                    'product_variant_id' => $productVariant->id,
                                    'số_lượng_ban_đầu' => $productVariant->quantity + $item->quantity,
                                    'số_lượng_mua' => $item->quantity,
                                    'số_lượng_còn_lại' => $productVariant->quantity
                                ]);
                            } else {
                                Log::warning('Không đủ số lượng sản phẩm', [
                                    'product_id' => $product_id,
                                    'product_variant_id' => $productVariant->id,
                                    'color' => $item->color,
                                    'size' => $item->size,
                                    'requested_quantity' => $item->quantity,
                                    'available_quantity' => $productVariant->quantity,
                                ]);
                            }
                        } else {
                            Log::error('Không tìm thấy ProductVariant', [
                                'product_id' => $product_id,
                                'color_name' => $item->color,
                                'size_name' => $item->size
                            ]);
                        }
                    }
                    Log::info('Xoá giỏ hàng.');
                    Cart::where('user_id', $user->id)->delete();
                    return $order;
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Đặt hàng thành công.',
                    'order_id' => $order->id,
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
                $latestOrder->orderItems()->delete(); // Xóa mục đơn hàng
                $latestOrder->delete(); // Xóa đơn hàn

                Log::error('Thiếu thông tin cấu hình VNPAY');
                Log::warning('Đã xóa đơn hàng do thanh toán thất bại');
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
                // Cập nhật trạng thái đơn hàng
                $order->update([
                    'payment_status' => Order::STATUS_PAYMENT_PAID,
                ]);

                // Cập nhật số lượng sản phẩm
                foreach ($order->orderItems as $item) {
                    $product_id = $item->product_id;
                    $productVariant = ProductVariant::with(['productColor', 'productSize'])
                        ->where('product_id', $product_id)
                        ->whereHas('productColor', function ($query) use ($item) {
                            $query->where('name', $item->color);
                        })
                        ->whereHas('productSize', function ($query) use ($item) {
                            $query->where('name', $item->size);
                        })
                        ->first();

                    Log::info('Thông tin sản phẩm:', [
                        'product_id' => $product_id,
                        'color_name' => $item->color,
                        'size_name' => $item->size,
                        'product_variant' => $productVariant ? [
                            'id' => $productVariant->id,
                            'quantity' => $productVariant->quantity
                        ] : 'null'
                    ]);

                    if ($productVariant) {
                        if ($productVariant->quantity >= $item->quantity) {
                            // Trừ số lượng
                            $productVariant->decrement('quantity', $item->quantity);

                            Log::info('Cập nhật số lượng thành công', [
                                'product_variant_id' => $productVariant->id,
                                'số_lượng_ban_đầu' => $productVariant->quantity + $item->quantity,
                                'số_lượng_mua' => $item->quantity,
                                'số_lượng_còn_lại' => $productVariant->quantity
                            ]);
                        } else {
                            Log::warning('Không đủ số lượng sản phẩm', [
                                'product_id' => $product_id,
                                'product_variant_id' => $productVariant->id,
                                'color' => $item->color,
                                'size' => $item->size,
                                'requested_quantity' => $item->quantity,
                                'available_quantity' => $productVariant->quantity,
                            ]);
                        }
                    } else {
                        Log::error('Không tìm thấy ProductVariant', [
                            'product_id' => $product_id,
                            'color_name' => $item->color,
                            'size_name' => $item->size
                        ]);
                    }
                }

                Log::info('Xóa giỏ hàng.');
                Cart::where('user_id', $order->user_id)->delete(); // Xóa giỏ hàng


                OrderSuccess::dispatch($order,$user);
                Log::info('Thông tin gửi mail:', [
                    'order' => $order,
                    'user' => $user
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Thanh toán thành công',
                    'data' => [
                        'response_code' => $request->vnp_ResponseCode,
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

    public function generateOrderCode()
    {
        do {
            // Tạo mã đơn hàng ngẫu nhiên gồm cả chữ và số
            $orderCode = '#' . strtoupper(Str::random(13));
        } while (Order::where('order_code', $orderCode)->exists()); // Gọi where() từ model Order

        return $orderCode;
    }


}
