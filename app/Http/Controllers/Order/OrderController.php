<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     *             required={"payment_method", "payment_status", "order_status"},
     *             @OA\Property(
     *                 property="payment_method",
     *                 type="string",
     *                 enum={"Thanh toán khi nhận hàng", "vnpay"},
     *                 example="Thanh toán khi nhận hàng",
     *                 description="Phương thức thanh toán"
     *             ),
     *             @OA\Property(
     *                 property="payment_status",
     *                 type="string",
     *                 enum={"unpaid", "paid", "failed"},
     *                 example="unpaid",
     *                 description="Trạng thái thanh toán"
     *             ),
     *             @OA\Property(
     *                 property="order_status",
     *                 type="string",
     *                 enum={"pending", "processing", "shipped", "delivered", "cancelled"},
     *                 example="pending",
     *                 description="Trạng thái đơn hàng"
     *             ),
     *             @OA\Property(
     *                 property="note",
     *                 type="string",
     *                 example="Giao hàng trong giờ hành chính",
     *                 description="Ghi chú đơn hàng"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Đặt hàng thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Đặt hàng thành công."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="order_id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(
     *                     property="address_id",
     *                     type="object",
     *                     @OA\Property(property="address_name", type="string", example="Số 1 Đại Cồ Việt"),
     *                     @OA\Property(property="phone_number", type="string", example="0123456789"),
     *                     @OA\Property(property="city", type="string", example="Hà Nội"),
     *                     @OA\Property(property="district", type="string", example="Hai Bà Trưng"),
     *                     @OA\Property(property="ward", type="string", example="Bách Khoa"),
     *                     @OA\Property(property="detail_address", type="string", example="Gần trường Đại học Bách Khoa")
     *                 ),
     *                 @OA\Property(property="payment_method", type="string", example="Thanh toán khi nhận hàng"),
     *                 @OA\Property(property="payment_status", type="string", example="unpaid"),
     *                 @OA\Property(property="order_status", type="string", example="pending"),
     *                 @OA\Property(property="total_amount", type="number", example=1000000),
     *                 @OA\Property(property="note", type="string", example="Giao hàng trong giờ hành chính"),
     *                 @OA\Property(
     *                     property="order_items",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="product_id", type="integer", example=1),
     *                         @OA\Property(property="color", type="string", example="Đen"),
     *                         @OA\Property(property="size", type="string", example="L"),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="price", type="number", example=500000)
     *                     )
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-20T15:30:00Z")
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
    public function order()
    {
        try {
            Log::info('Bắt đầu quy trình đặt hàng.');

            // Kiểm tra user đăng nhập
            $user = auth('api')->user();
            if (!$user) {
                Log::warning('Người dùng chưa đăng nhập khi thực hiện đặt hàng.');
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn cần đăng nhập để sử dụng tính năng này.'
                ], 401);
            }

            // Kiểm tra giỏ hàng trước khi bắt đầu transaction
            $cart = Cart::query()->where('user_id', $user->id)->get();
            if ($cart->isEmpty()) {
                Log::warning('Giỏ hàng của người dùng đang rỗng', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Giỏ hàng không có sản phẩm nào.'
                ], 404);
            }

            $result = DB::transaction(function () use ($user, $cart) {
                Log::info('Người dùng đăng nhập: ', ['user_id' => $user->id]);
                Log::info('Giỏ hàng của người dùng: ', $cart->toArray() ?: ['cart' => []]);


                $totalAmount = 0;
                $dataItem = [];

                foreach ($cart as $cartItem) {
                    $totalAmount += $cartItem->quantity * ($cartItem->product->price_sale ?? $cartItem->product->price_regular);

                    $dataItem[] = [
                        'product_id' => $cartItem->product_id,
                        'color' => $cartItem->color,
                        'size' => $cartItem->size,
                        'quantity' => $cartItem->quantity,
                        'price' => $cartItem->product->price_sale ?: $cartItem->product->price_regular
                    ];
                }

                Log::info('Tổng số tiền đơn hàng:', ['totalAmount' => $totalAmount]);

                // Kiểm tra địa chỉ mặc định
                $defaultAddress = $user->addresses()->where('is_default', true)->first();
                if (!$defaultAddress) {
                    Log::warning('Người dùng chưa có địa chỉ mặc định.', ['user_id' => $user->id]);
                    return [
                        'success' => false,
                        'message' => 'Bạn cần thêm địa chỉ trước khi đặt hàng.',
                        'status' => 400
                    ];
                }

                // Tạo đơn hàng và các items
                $order = Order::query()->create([
                    'user_id' => $user->id,
                    'address_id' => $defaultAddress->id,
                    'payment_method' => \request('payment_method'),
                    'payment_status' => \request('payment_status') ?: Order::STATUS_PAYMENT_UNPAID,
                    'order_status' => \request('order_status') ?: Order::STATUS_ORDER_PENDING,
                    'total_amount' => $totalAmount,
                    'note' => request('note'),
                ]);

                foreach ($dataItem as $item) {
                    $item['order_id'] = $order->id;
                    OrderItem::query()->create($item);
                }
                Log::info('Đặt hàng thành công.', ['user_id' => $user->id]);

                Cart::query()->where('user_id', $user->id)->delete();
                Log::info('Xoá giỏ hàng  của người dùng.', ['user_id' => $user->id]);
                return [
                    'success' => true,
                    'message' => 'Đặt hàng thành công.',
                    'status' => 201
                ];

            });
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message']
            ], $result['status']);

        } catch (ModelNotFoundException $exception) {
            Log::error('Không tìm thấy lớp dữ liệu.', ['error' => $exception->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy lớp.',
            ], 404);

        } catch (Exception $e) {
            Log::error('Đặt hàng không thành công.', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Không thể đặt hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
