<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderManagementController extends Controller
{
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
    public function detall($id)
    {
        $order = Order::with('orderItems')->findOrFail($id);
        return response()->json($order);
       
    if (!Auth::check()) {
        return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
    }

    $order = Order::with(['orderItems', 'address', 'user'])->find($id); 


    if (!$order) {
        return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
    }


    return response()->json([
        'id' => $order->id,
        'name' => $order->user->name, 
        'total_amount' => $order->total_amount,
        'address' => $order->address->address_name,
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

    // Cập nhật trạng thái đơn hàng
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
