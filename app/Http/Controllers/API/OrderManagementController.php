<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderManagementController extends Controller
{
/**
 * @OA\Get(
 *     path="/api/admin/orders",
 *     summary="Get all orders",
 *     tags={"Orders Management"},
 *     security={{"Bearer": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful retrieval of all orders",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1, description="Order ID"),
 *                 @OA\Property(property="customer_name", type="string", example="Nguyễn Văn A", description="Customer name"),
 *                 @OA\Property(property="total_amount", type="number", format="float", example=150.00, description="Total order amount"),
 *                 @OA\Property(property="order_status", type="string", example="completed", description="Order status"),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-10-19T12:00:00Z", description="Order creation timestamp"),
 *             )
 *         )
 *     ),
 *     @OA\Response(response=404, description="No orders found")
 * )
 */
public function index()
{
    // Kiểm tra người dùng đã đăng nhập chưa
    if (!Auth::check()) {
        return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
    }
    $orders = Order::all(); 
    if ($orders->isEmpty()) {
        return response()->json(['message' => 'Không có đơn hàng nào', 'orders' => []], 404);
    }
    return response()->json([
        'message' => "Có {$orders->count()} đơn hàng.",
        'order_count' => $orders->count(),
        'orders' => $orders, 
    ], 200);
}
/**
 * @OA\Get(
 *     path="/api/admin/orders/{id}",
 *     summary="Lấy chi tiết đơn hàng theo ID",
 *     tags={"Orders Management"},
 *     security={{"Bearer": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer"),
 *         description="ID của đơn hàng"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Lấy thành công chi tiết đơn hàng",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1, description="ID đơn hàng"),
 *             @OA\Property(property="name", type="string", example="Nguyễn Văn A", description="Tên khách hàng"),
 *             @OA\Property(property="total_amount", type="number", format="float", example=150.00, description="Tổng số tiền đơn hàng"),
 *             @OA\Property(property="address", type="string", example="Địa chỉ", description="Địa chỉ giao hàng"),
 *             @OA\Property(property="payment_method", type="string", example="Thẻ tín dụng", description="Phương thức thanh toán"),
 *             @OA\Property(property="payment_status", type="string", example="Đã thanh toán", description="Trạng thái thanh toán"),
 *             @OA\Property(property="order_status", type="string", example="Hoàn thành", description="Trạng thái đơn hàng"),
 *             @OA\Property(property="note", type="string", example="Ghi chú", description="Ghi chú của đơn hàng"),
 *             @OA\Property(property="created_at", type="string", format="date-time", example="2024-10-19T12:00:00Z", description="Thời gian tạo đơn hàng"),
 *             @OA\Property(property="updated_at", type="string", format="date-time", example="2024-10-19T12:00:00Z", description="Thời gian cập nhật đơn hàng"),
 *             @OA\Property(
 *                 property="order_items",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="order_id", type="integer", example=1, description="ID đơn hàng"),
 *                     @OA\Property(property="product_id", type="integer", example=101, description="ID sản phẩm"),
 *                     @OA\Property(property="quantity", type="integer", example=2, description="Số lượng sản phẩm"),
 *                     @OA\Property(property="price", type="number", format="float", example=75.00, description="Giá của sản phẩm"),
 *                     @OA\Property(property="size", type="string", example="L", description="Kích thước của sản phẩm"),
 *                     @OA\Property(property="color", type="string", example="Đỏ", description="Màu sắc của sản phẩm"),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-10-19T12:00:00Z", description="Thời gian tạo sản phẩm trong đơn hàng"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-10-19T12:00:00Z", description="Thời gian cập nhật sản phẩm trong đơn hàng")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Không được phép"),
 *     @OA\Response(response=404, description="Không tìm thấy đơn hàng")
 * )
 */

 public function detall($id)
 {
     if (!Auth::check()) {
         return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
     }
 
     $order = Order::with(['orderItems', 'address', 'user'])->find($id);
 
     if (!$order) {
         return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
     }
 
     return response()->json([
        'id' => $order->id,
        'name' => $order->user ? $order->user->name : 'N/A',
        'total_amount' => $order->total_amount,
        'address' => $order->address ? [
            'id' => $order->address->id,
            'address_name' => $order->address->address_name,
            'phone_number' => $order->address->phone_number,
            'city' => $order->address->city,
            'district' => $order->address->district,
            'ward' => $order->address->ward,
            'detail_address' => $order->address->detail_address,
        ] : 'N/A',
        'payment_method' => $order->payment_method,
        'payment_status' => $order->payment_status,
        'order_status' => $order->order_status,
        'note' => $order->note,
        'created_at' => $order->created_at,
        'updated_at' => $order->updated_at,
        'order_items' => $order->orderItems->map(function ($item) {
            return [
                'order_id' => $item->order_id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'size' => $item->size,
                'color' => $item->color,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        }),
    ]);
    
 }
 
/**
 * @OA\Patch(
 *     path="/api/admin/orders/{id}/status",
 *     tags={"Orders Management"},
 *     security={{"Bearer": {}}},
 *     summary="Cập nhật trạng thái đơn hàng",
 *     description="Cập nhật trạng thái của đơn hàng theo ID.",
 *     operationId="updateOrderStatus",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID của đơn hàng cần cập nhật.",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="order_status", type="string", example="completed"),
 *         ),
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Cập nhật trạng thái đơn hàng thành công.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Cập nhật trạng thái đơn hàng thành công.")
 *         ),
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Không tìm thấy đơn hàng.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Không tìm thấy đơn hàng.")
 *         ),
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Dữ liệu không hợp lệ.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Dữ liệu không hợp lệ.")
 *         ),
 *     )
 * )
 */
public function updateStatus(Request $request, $id)
{
    $order = Order::findOrFail($id);
    $order->order_status = $request->input('order_status');
    $order->save();

    return response()->json(['message' => 'Cập nhật trạng thái đơn hàng thành công.']);
}


    // Cập nhật thông tin đơn hàng
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->update($request->all()); 
        return response()->json(['message' => 'Cập nhật đơn hàng thành công.']);
    }

    // Xử lý yêu cầu hoàn trả
    public function refund(Request $request, $id)
    {
        // Logic hoàn trả (chưa triển khai)
        return response()->json(['message' => 'Yêu cầu hoàn trả đã được xử lý thành công.']);
    }

    // Quản lý hủy đơn
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete(); // Xóa đơn hàng
        return response()->json(['message' => 'Đơn hàng đã được xóa thành công.']);
    }

    // Theo dõi tình trạng giao hàng
    public function tracking($id)
    {
        // Logic theo dõi giao hàng (chưa triển khai)
        return response()->json(['message' => 'Thông tin theo dõi đã được lấy thành công.']);
    }
    
    // Tìm kiếm đơn hàng
    public function search(Request $request)
    {
        $query = $request->input('query');
        $orders = Order::where('name', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%")
                    ->with('orderItems') // Tải trước orderItems
                    ->get();

        return response()->json($orders);
    }

    // Lọc đơn hàng theo ngày
    public function filterByDate(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
                       ->with('orderItems') // Tải trước orderItems
                       ->get();

        return response()->json($orders);
    }

    // Thêm mục đơn hàng cho một đơn hàng
    public function addOrderItem(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);

        // Xác thực dữ liệu
        $validatedData = $request->validate([
            'size' => 'required|string',
            'color' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'product_id' => 'required|exists:products,id', 
        ]);

        $orderItem = new OrderItem($validatedData);
        $orderItem->order_id = $order->id; 
        $orderItem->save(); 

        return response()->json(['message' => 'Mục đơn hàng đã được thêm thành công.']);
    }

    // Cập nhật mục đơn hàng
    public function updateOrderItem(Request $request, $orderId, $itemId)
    {
        $orderItem = OrderItem::where('order_id', $orderId)->findOrFail($itemId);

        // Xác thực dữ liệu
        $validatedData = $request->validate([
            'size' => 'string',
            'color' => 'string',
            'quantity' => 'integer|min:1',
        ]);

        $orderItem->update($validatedData);

        return response()->json(['message' => 'Mục đơn hàng đã được cập nhật thành công.']);
    }

    // Xóa mục đơn hàng
    public function destroyOrderItem($orderId, $itemId)
    {
        $orderItem = OrderItem::where('order_id', $orderId)->findOrFail($itemId);
        $orderItem->delete();

        return response()->json(['message' => 'Mục đơn hàng đã được xóa thành công.']);
    }
}
