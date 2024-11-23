<?php

namespace App\Http\Controllers\API\Order;

use App\Events\OrderDelivered as EventsOrderDelivered;
use App\Http\Controllers\Controller;
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
     *         @OA\Schema(type="string", enum={"processing", "pending", "shipping"})
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
        $validStatuses = ['shipping'];

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
                    'phone_number' => $order->address->phone_number ?? 'N/A',
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

    /**
     * @OA\Post(
     *     path="/api/orders/{id}/confirm",
     *     summary="Xác nhận đơn hàng",
     *     tags={"Delivery Management"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của đơn hàng cần xác nhận",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đơn hàng đã được xác nhận thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Đơn hàng đã được xác nhận.")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Đơn hàng không tồn tại."),
     *     @OA\Response(response=403, description="Bạn không có quyền xác nhận đơn hàng.")
     * )
     */
    public function confirmOrder($id)
    {
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'staff'])) {
            return response()->json(['message' => 'Bạn không có quyền xác nhận đơn hàng.'], 403);
        }
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
        }

        $order->order_status = 'shipping';

        $order->save();
        return response()->json(['message' => 'Đơn hàng đã được xác nhận.']);
    }

    /**
     * @OA\Post(
     *     path="/api/orders/confirm-delivery/{id}",
     *     summary="Xác nhận giao hàng thành công",
     *     tags={"Delivery Management"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của đơn hàng cần xác nhận giao hàng",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="recipient_name",
     *         in="query",
     *         required=false,
     *         description="Tên người nhận",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="signature",
     *         in="query",
     *         required=false,
     *         description="Chữ ký của người nhận",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đơn hàng đã được xác nhận giao hàng thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Đơn hàng đã được xác nhận giao hàng thành công.")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Đơn hàng không tồn tại."),
     *     @OA\Response(response=403, description="Bạn không có quyền xác nhận giao hàng.")
     * )
     */
    public function confirmDelivery($id, Request $request)
    {
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'staff'])) {
            return response()->json(['message' => 'Bạn không có quyền xác nhận giao hàng.'], 403);
        }

        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
        }
        if ($order->order_status === 'delivered') {
            return response()->json(['message' => 'Đơn hàng này đã được giao.'], 400);
        }  
        if ($order->order_status !== 'shipping') {
            return response()->json(['message' => 'Đơn hàng này chưa được giao.'], 400);
        }
        $order->order_status = 'delivered';
        $order->delivered_at = now();
        $order->recipient_name = $request->input('recipient_name', null);
        $order->recipient_signature = $request->input('signature', null);
        $order->save();
        // event(new EventsOrderDelivered($order));
        return response()->json(['message' => 'Đơn hàng đã được xác nhận giao hàng thành công.']);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/orders/update-status/{id}",
     *     summary="Cập nhật trạng thái giao hàng",
     *     tags={"Delivery Management"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của đơn hàng cần cập nhật trạng thái",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=true,
     *         description="Trạng thái mới của đơn hàng (có thể là: 'failed', 'cancelled', 'reschedule')",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="reason",
     *         in="query",
     *         required=false,
     *         description="Lý do hủy hoặc giao thất bại",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="reschedule_time",
     *         in="query",
     *         required=false,
     *         description="Thời gian giao lại (nếu có, định dạng: 'Y-m-d H:i:s')",
     *         @OA\Schema(type="string", format="date-time")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Trạng thái giao hàng đã được cập nhật thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Trạng thái giao hàng đã được cập nhật thành công.")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Đơn hàng không tồn tại."),
     *     @OA\Response(response=403, description="Bạn không có quyền quản lý trạng thái giao hàng."),
     *     @OA\Response(response=400, description="Trạng thái không hợp lệ.")
     * )
     */
    public function updateDeliveryStatus($id, Request $request)
    {
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'staff'])) {
            return response()->json(['message' => 'Bạn không có quyền quản lý trạng thái giao hàng.'], 403);
        }

        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
        }

        $status = $request->input('status');
        $reason = $request->input('reason', null);

        if ($order->order_status === 'shipping') {
            if ($status === 'failed') {
                $order->order_status = 'failed';
                $order->note = $reason;
            } elseif ($status === 'cancelled') {
                $order->order_status = 'cancelled';
                $order->note = $reason;
            } else {
                return response()->json(['message' => 'Trạng thái không hợp lệ.'], 400);
            }
        } elseif ($order->order_status === 'processing') {
            if ($status === 'reschedule') {
                $order->order_status = 'rescheduled';
                $order->reschedule_time = $request->input('reschedule_time');
            } else {
                return response()->json(['message' => 'Trạng thái không hợp lệ cho đơn hàng chưa giao.'], 400);
            }
        } else {
            return response()->json(['message' => 'Trạng thái đơn hàng hiện tại không cho phép cập nhật.'], 400);
        }

        $order->save();
        event(new EventsOrderDelivered($order));
        return response()->json(['message' => 'Trạng thái giao hàng đã được cập nhật thành công.', 'status' => $order->order_status]);
    }
}
