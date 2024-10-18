<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem; 
use Illuminate\Http\Request;


class OrderManagementController extends Controller
{
  
    public function index()
    {
        $orders = Order::with('orderItems')->get(); 
        return response()->json($orders);
    }

    // Xem chi tiết đơn hàng
    public function show($id)
    {
        $order = Order::with('orderItems')->findOrFail($id); 
        return response()->json($order);
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
