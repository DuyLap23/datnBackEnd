<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends Controller
{
  /**
 * @OA\Get(
 *     path="/api/orders/ready-to-deliver",
 *     summary="Nhận danh sách đơn hàng sẵn sàng giao",
 *     tags={"Delivery Management"},
 *     security={{"Bearer": {}}},
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         description="Trạng thái của đơn hàng để lọc",
 *         required=false,
 *         @OA\Schema(type="string", enum={"processing", "pending", "shipped"})
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful retrieval of ready-to-deliver orders",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="order_id", type="integer", example=1, description="Mã ID của đơn hàng"),
 *                 @OA\Property(property="customer_name", type="string", example="Nguyễn Văn A", description="Tên khách hàng"),
 *                 @OA\Property(property="address", type="string", example="123 Đường ABC", description="Địa chỉ giao hàng"),
 *                 @OA\Property(property="products", type="array", @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="product_name", type="string", example="Sản phẩm A"),
 *                     @OA\Property(property="quantity", type="integer", example=2),
 *                     @OA\Property(property="price", type="number", format="float", example=100000),
 *                     @OA\Property(property="image", type="string", example="link_to_image.jpg"),
 *                 )),
 *                 @OA\Property(property="total_quantity", type="integer", example=2),
 *                 @OA\Property(property="total_amount", type="number", format="float", example=200000),
 *                 @OA\Property(property="order_status", type="string", example="processing"),
 *                 @OA\Property(property="delivery_time", type="string", format="date-time", example="2024-10-19T12:00:00Z"),
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=404, description="No orders found")
 * )
 */
public function index(Request $request)
{

    if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'staff'])) {
        return response()->json(['message' => 'Bạn không có quyền tìm kiếm đơn hàng.'], 403);
    }

    $status = $request->query('status');
    $validStatuses = ['processing', 'pending', 'shipped'];

    if ($status && !in_array($status, $validStatuses)) {
        return response()->json(['message' => 'Trạng thái không hợp lệ.'], 400);
    }

    $ordersQuery = Order::with(['orderItems.product', 'address']);

    if ($status) {
        $ordersQuery->where('order_status', $status);
    } else {
        $ordersQuery->whereIn('order_status', $validStatuses);
    }

    $orders = $ordersQuery->get();
    if ($orders->isEmpty()) {
        return response()->json(['message' => 'Không có đơn hàng nào cần giao.'], 404);
    }
    $orderCountByStatus = $orders->groupBy('order_status')->map(function ($group) {
        return $group->count();
    });
    $totalOrders = $orders->count();
    $message = "Tổng số đơn hàng cần giao: $totalOrders";
    return response()->json([
        'message' => $message,
        'orders' => $orders->map(function ($order) {
            return [
                'order_id' => $order->id,
                'customer_name' => $order->user->name ?? 'N/A',
                'address' => $order->address ? implode(', ', [
                    $order->address->detail_address,
                    $order->address->ward,
                    $order->address->district,
                    $order->address->city,
                ]) : 'N/A',
                'products' => $order->orderItems->map(function ($item) {
                    return [
                        'product_name' => $item->product->name ?? 'N/A',
                        'quantity' => $item->quantity,
                        'price' => $item->product->price_sale ?? $item->product->price_regular ?? 0.0,
                        'image' => $item->product->img_thumbnail ?? 'N/A',
                    ];
                }),
                'total_quantity' => $order->orderItems->sum('quantity'),
                'total_amount' => $order->total_amount,
                'order_status' => $order->order_status,
                'delivery_time' => $order->created_at->toIso8601String(),
            ];
        }),
        'order_count_by_status' => $orderCountByStatus
    ]);
}

    
}
