<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderTrackingController extends Controller
{
/**
     * @OA\Get(
     *     tags={"Order Tracking"},
     *     path="/api/admin/orders/order-status-tracking",
     *     security={{"Bearer": {}}},
     *     summary="Lấy danh sách tất cả đơn hàng cùng thông tin sản phẩm và người đặt.",
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách đơn hàng với trạng thái, thời gian, sản phẩm và người đặt.",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="address_id", type="integer", example=101),
     *                 @OA\Property(property="payment_method", type="string", example="credit_card"),
     *                 @OA\Property(property="payment_status", type="string", example="paid"),
     *                 @OA\Property(property="order_status", type="string", example="delivered"),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=150.00),
     *                 @OA\Property(property="note", type="string", example="Ghi chú cho đơn hàng"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-02T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-02T12:30:00Z"),
     *                 @OA\Property(property="deleted_at", type="string", format="date-time", example="2024-11-02T13:00:00Z"),
     *                 @OA\Property(property="delivered_at", type="string", format="date-time", example="2024-11-02T13:00:00Z"),
     *                 @OA\Property(property="cancelled_by", type="integer", example=2),
     *                 @OA\Property(property="received_at", type="string", format="date-time", example="2024-11-02T13:00:00Z"),
     *                 @OA\Property(property="current_status", type="string", example="Đơn hàng đã được giao"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Nguyễn Văn A"),
     *                     @OA\Property(property="email", type="string", example="nguyenvana@example.com")
     *                 ),
     *                 @OA\Property(property="products", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="product_id", type="integer", example=101),
     *                         @OA\Property(property="product_name", type="string", example="Sản phẩm A"),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="price", type="number", format="float", example=150.00)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Không có quyền truy cập.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Bạn không có quyền theo dõi đơn hàng.")
     *         )
     *     )
     * )
     */
    public function index()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Bạn không có quyền theo dõi đơn hàng.'], 403);
        }
    
        $orders = Order::with(['user', 'orderItems.product'])->get(); 
    
        $orderDetails = $orders->map(function($order) {
            $currentStatus = match ($order->order_status) {
                'pending' => 'Đơn hàng đang chờ xử lý',
                'delivered' => 'Đơn hàng đã được giao',
                'completed' => 'Đơn hàng đã hoàn thành',
                'cancelled' => 'Đơn hàng đã bị hủy',
                default => 'Trạng thái không xác định'
            };
    
            return [
                'id' => $order->id,
                'user_id' => $order->user_id,
                'address_id' => $order->address_id,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'order_status' => $order->order_status,
                'total_amount' => $order->total_amount,
                'note' => $order->note,
                'created_at' => $this->formatDate($order->created_at),
                'updated_at' => $this->formatDate($order->updated_at),
                'deleted_at' => $this->formatDate($order->deleted_at),
                'delivered_at' => $this->formatDate($order->delivered_at),
                'cancelled_by' => $order->cancelled_by,
                'received_at' => $this->formatDate($order->received_at),
                'current_status' => $currentStatus, 
                'user' => [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                ],
                'products' => $order->orderItems->map(function($orderItem) {
                    return [
                        'product_id' => $orderItem->product->id,
                        'product_name' => $orderItem->product->name,
                        'quantity' => $orderItem->quantity,
                        'price' => $orderItem->price,
                    ];
                }),
            ];
        });
    
        return response()->json($orderDetails, 200);
    }
    
    private function formatDate($date)
    {
        if ($date instanceof \Carbon\Carbon) {
            return $date->toISOString();
        } elseif (is_string($date)) {
            return \Carbon\Carbon::parse($date)->toISOString();
        }
    
        return null; 
    }
    
}
