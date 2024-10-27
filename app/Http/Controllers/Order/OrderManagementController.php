<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderManagementController extends Controller
{  /**
    * @OA\Get(
    *     path="/api/admin/orders",
    *     summary="Get all orders with optional status filter",
    *     tags={"Orders Admin Management"},
    *     security={{"Bearer": {}}},
    *     @OA\Parameter(
    *         name="status",
    *         in="query",
    *         description="Order status filter (all, pending, shipped, delivered, cancelled, returned_refunded)",
    *         required=false,
    *         @OA\Schema(type="string")
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successful retrieval of all orders with optional status filter",
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
    *     @OA\Response(response=401, description="Unauthorized"),
    *     @OA\Response(response=404, description="No orders found")
    * )
    */
   public function index(Request $request)
   {
    if (!Auth::check() || Auth::user()->role !== 'admin') {
        return response()->json(['message' => 'Bạn không có quyền cập nhật trạng thái đơn hàng.'], 403);
    }
   
       // Lấy trạng thái đơn hàng từ query string, mặc định là 'all'
       $status = $request->query('status', 'all');
       $query = Order::query();
   
       // Lọc đơn hàng theo trạng thái
       switch ($status) {
           case 'pending':
               $query->where('order_status', 'pending'); // chờ thanh toán
               break;
           case 'shipped':
               $query->where('order_status', 'shipped'); // đang vận chuyển
               break;
           case 'delivered':
               $query->where('order_status', 'delivered'); // đã hoàn thành
               break;
           case 'cancelled':
               $query->where('order_status', 'cancelled'); // đã hủy
               break;
           case 'returned_refunded':
               $query->where('order_status', 'returned_refunded'); // trả hàng/hoàn tiền
               break;
           case 'all':
           default:
               break;
       }
   
       // Lấy danh sách đơn hàng
       $orders = $query->with(['orderItems.product'])->get();  
   
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
 *     tags={"Orders Admin Management"},
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
     if (!Auth::check() || Auth::user()->role !== 'admin') {
         return response()->json(['message' => 'Bạn không có quyền truy cập chi tiết đơn hàng.'], 403);
     }
 

     $order = Order::with(['orderItems.product', 'address', 'user'])
         ->where('user_id', Auth::id()) 
         ->findOrFail($id);
    $totalAllOrders = Order::sum('total_amount');
     return response()->json([
         'order_id' => $order->id,
         'name' => $order->user ? $order->user->name : 'N/A',
         'email' => $order->user ? $order->user->email : 'N/A',
         'total_amount' => $order->total_amount,
         'total_all_orders' => $totalAllOrders,
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
                 'product' => $item->product ? [
                     'id' => $item->product->id,
                     'name' => $item->product->name,
                     'description' => $item->product->description,
                     'price_regular' => $item->product->price_regular,
                     'price_sale' => $item->product->price_sale,
                     'category' => $item->product->category ? $item->product->category->name : 'N/A',
                     'img_thumbnail' => $item->product->img_thumbnail,
                 ] : 'N/A',
             ];
         }),
     ]);
 }
 
/**
 * @OA\Get(
 *     path="/api/admin/orders/search",
 *     summary="Tìm kiếm đơn hàng",
 *     tags={"Orders Admin Management"},
 *     security={{"Bearer": {}}},
 *     @OA\Parameter(
 *         name="query",
 *         in="query",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="Từ khóa tìm kiếm (tên, email của khách hàng hoặc mã đơn hàng)"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Danh sách đơn hàng tìm thấy",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", description="ID đơn hàng"),
 *                 @OA\Property(property="user_name", type="string", description="Tên khách hàng"),
 *                 @OA\Property(property="user_email", type="string", description="Email khách hàng"),
 *                 @OA\Property(property="total_amount", type="number", format="float", description="Tổng số tiền đơn hàng"),
 *                 @OA\Property(property="created_at", type="string", format="date-time", description="Thời gian tạo đơn hàng"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", description="Thời gian cập nhật đơn hàng"),
 *                 @OA\Property(
 *                     property="order_items",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="order_id", type="integer", description="ID đơn hàng"),
 *                         @OA\Property(property="product_id", type="integer", description="ID sản phẩm"),
 *                         @OA\Property(property="product_name", type="string", description="Tên sản phẩm"), 
 *                         @OA\Property(property="quantity", type="integer", description="Số lượng sản phẩm"),
 *                         @OA\Property(property="price", type="number", format="float", description="Giá của sản phẩm"),
 *                         @OA\Property(property="size", type="string", description="Kích thước của sản phẩm"),
 *                         @OA\Property(property="color", type="string", description="Màu sắc của sản phẩm"),
 *                         @OA\Property(property="img_thumbnail", type="string", description="Ảnh thu nhỏ của sản phẩm"),
 *                         @OA\Property(property="created_at", type="string", format="date-time", description="Thời gian tạo sản phẩm trong đơn hàng"),
 *                         @OA\Property(property="updated_at", type="string", format="date-time", description="Thời gian cập nhật sản phẩm trong đơn hàng")
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=404, description="Không tìm thấy đơn hàng"),
 *     @OA\Response(response=403, description="Không được phép")
 * )
 */
public function search(Request $request)
{
    if (!Auth::check() || Auth::user()->role !== 'admin') {
        return response()->json(['message' => 'Bạn không có quyền tìm kiếm đơn hàng.'], 403);
    }

    $query = $request->input('query');

    // Kiểm tra xem truy vấn có trống không
    if (empty($query)) {
        return response()->json(['message' => 'Từ khóa tìm kiếm không được để trống.'], 400);
    }

    $orders = Order::with(['orderItems.product'])
        ->where('id', $query) // Tìm theo ID đơn hàng
        ->orWhereHas('user', function ($q) use ($query) {
            $q->where('name', 'LIKE', "%{$query}%")
              ->orWhere('email', 'LIKE', "%{$query}%");
        })
        ->get(); // Sử dụng get() thay vì firstOrFail()

    // Kiểm tra nếu không có kết quả
    if ($orders->isEmpty()) {
        return response()->json(['message' => 'Không tìm thấy đơn hàng nào phù hợp với từ khóa tìm kiếm.'], 404);
    }

    // Xử lý và trả về dữ liệu
    $response = $orders->map(function ($order) {
        return [
            'id' => $order->id,
            'user_name' => $order->user ? $order->user->name : 'N/A',
            'user_email' => $order->user ? $order->user->email : 'N/A',
            'total_amount' => $order->total_amount,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
            'order_items' => $order->orderItems->map(function ($item) {
                return [
                    'order_id' => $item->order_id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product ? $item->product->name : null,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'size' => $item->size,
                    'color' => $item->color,
                    'img_thumbnail' => $item->product ? $item->product->img_thumbnail : null,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            }),
        ];
    });

    return response()->json($response);
}

/**
 * @OA\Get(
 *     path="/api/admin/orders/filter",
 *     operationId="filterByDate",
 *     tags={"Orders Admin Management"},
 *     summary="Lọc đơn hàng theo ngày",
 *     description="Trả về danh sách đơn hàng trong khoảng thời gian đã chỉ định.",
 *     security={{"Bearer": {}}},
 *     @OA\Parameter(
 *         name="start_date",
 *         in="query",
 *         description="Ngày bắt đầu (Y-m-d)",
 *         required=true,
 *         @OA\Schema(type="string", format="date")
 *     ),
 *     @OA\Parameter(
 *         name="end_date",
 *         in="query",
 *         description="Ngày kết thúc (Y-m-d)",
 *         required=true,
 *         @OA\Schema(type="string", format="date")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Danh sách đơn hàng",
 *         @OA\JsonContent(type="array", @OA\Items(
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="user_name", type="string", example="Nguyen Van A"),
 *             @OA\Property(property="user_email", type="string", example="a@gmail.com"),
 *             @OA\Property(property="total_amount", type="number", format="float", example=150.75),
 *             @OA\Property(property="created_at", type="string", format="date-time", example="2024-10-26T12:34:56"),
 *             @OA\Property(property="updated_at", type="string", format="date-time", example="2024-10-27T12:34:56"),
 *             @OA\Property(property="order_items", type="array", @OA\Items(
 *                 @OA\Property(property="order_id", type="integer", example=1),
 *                 @OA\Property(property="product_id", type="integer", example=1),
 *                 @OA\Property(property="product_name", type="string", example="Sản phẩm A"),
 *                 @OA\Property(property="quantity", type="integer", example=2),
 *                 @OA\Property(property="price", type="number", format="float", example=75.50),
 *                 @OA\Property(property="size", type="string", example="M"),
 *                 @OA\Property(property="color", type="string", example="Đỏ"),
 *                 @OA\Property(property="img_thumbnail", type="string", example="http://example.com/image.jpg"),
 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-10-26T12:34:56"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-10-27T12:34:56")
 *             ))
 *         ))
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Ngày bắt đầu và ngày kết thúc là bắt buộc.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Ngày bắt đầu và ngày kết thúc là bắt buộc.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Không tìm thấy đơn hàng nào trong khoảng thời gian đã cho.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Không tìm thấy đơn hàng nào trong khoảng thời gian đã cho.")
 *         )
 *     ),
 * )
 */
public function filterByDate(Request $request)
{
    if (!Auth::check() || Auth::user()->role !== 'admin') {
        return response()->json(['message' => 'Bạn không có quyền tìm kiếm đơn hàng.'], 403);
    }
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    if (!$startDate || !$endDate) {
        return response()->json(['message' => 'Ngày bắt đầu và ngày kết thúc không được để trống.'], 400);
    }

    try {
        // Chuyển đổi sang đối tượng Carbon
        $startDate = Carbon::parse($startDate)->startOfSecond();
        $endDate = Carbon::parse($endDate)->endOfSecond();

        // Lọc đơn hàng theo ngày
        $orders = Order::with(['orderItems.product'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng nào trong khoảng thời gian đã chỉ định.'], 404);
        }

        return response()->json($orders);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
    }
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
