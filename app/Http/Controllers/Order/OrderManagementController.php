<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderManagementController extends Controller
{  /**
    * @OA\Get(
    *     path="/api/admin/orders",
    *     summary="Nhận tất cả các đơn hàng có bộ lọc trạng thái tùy chọn",
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
       $query->whereNull('deleted_at');
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
 * @OA\Patch(
 *     path="/api/admin/orders/status/{id}",
 *     summary="Cập nhật trạng thái của đơn hàng",
 *     tags={"Orders Admin Management"},
 *     security={{"Bearer": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer"),
 *         description="ID của đơn hàng cần cập nhật"
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             required={"status"},
 *             @OA\Property(
 *                 property="status",
 *                 type="string",
 *                 description="Trạng thái mới của đơn hàng (pending, shipped, delivered, cancelled, returned_refunded)",
 *                 example="shipped"
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Cập nhật thành công trạng thái đơn hàng",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Trạng thái đơn hàng đã được cập nhật thành công."),
 *             @OA\Property(
 *                 property="order",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1, description="ID của đơn hàng"),
 *                 @OA\Property(property="order_status", type="string", example="shipped", description="Trạng thái mới của đơn hàng"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-10-19T12:00:00Z", description="Thời gian cập nhật trạng thái đơn hàng")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=400, description="Dữ liệu không hợp lệ"),
 *     @OA\Response(response=401, description="Không được phép"),
 *     @OA\Response(response=404, description="Không tìm thấy đơn hàng")
 * )
 */
public function updateStatus(Request $request, $id)
{
    if (!Auth::check() || Auth::user()->role !== 'admin') {
        return response()->json(['message' => 'Bạn không có quyền cập nhật trạng thái đơn hàng.'], 403);
    }

    $request->validate([
        'status' => 'required|string|in:pending,shipped,delivered,cancelled,returned_refunded',
    ]);

    $order = Order::find($id);
    if (!$order) {
        return response()->json(['message' => 'Không tìm thấy đơn hàng.'], 404);
    }

    $order->order_status = $request->status;
    $order->save();

    return response()->json([
        'message' => 'Trạng thái đơn hàng đã được cập nhật thành công.',
        'order' => [
            'id' => $order->id,
            'order_status' => $order->order_status,
            'updated_at' => $order->updated_at,
        ],
    ], 200);
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
    Log::info('Từ khóa tìm kiếm: ' . $query);
    if (empty($query)) {
        Log::error('Từ khóa tìm kiếm không được để trống.');
        return response()->json(['message' => 'Từ khóa tìm kiếm không được để trống.'], 400);
    }

    try {
        $orders = Order::with(['orderItems.product', 'address', 'user'])
            ->where('deleted_at', null)
            ->where('id', $query)
            ->orWhereHas('user', function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%");
            })
            ->get();
        if ($orders->isEmpty()) {
            Log::info('Không tìm thấy đơn hàng nào phù hợp với từ khóa: ' . $query);
            return response()->json(['message' => 'Không tìm thấy đơn hàng nào phù hợp với từ khóa tìm kiếm.'], 404);
        }

        Log::info('Tìm thấy ' . $orders->count() . ' đơn hàng phù hợp.');
        $totalAllOrders = Order::sum('total_amount');
        $response = $orders->map(function ($order) use ($totalAllOrders) {
            return [
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
            ];
        });

        Log::info('Trả về kết quả thành công.');
        return response()->json($response);

    } catch (\Exception $e) {
        Log::error('Có lỗi xảy ra trong quá trình tìm kiếm: ' . $e->getMessage());
        return response()->json(['message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
    }
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
        $startDate = Carbon::createFromFormat('Y-m-d H:i:s', $startDate);
        $endDate = Carbon::createFromFormat('Y-m-d H:i:s', $endDate);

        if (!$startDate || !$endDate) {
            throw new \Exception('Ngày không hợp lệ.');
        }
        $startDate = $startDate->startOfDay();
        $endDate = $endDate->endOfDay();
        $orders = Order::with(['orderItems.product'])
            ->whereNull('deleted_at')    
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng nào trong khoảng thời gian đã chỉ định.'], 404);
        }

        $orderCount = $orders->count();
        return response()->json([
            'message' => 'Đã tìm thấy đơn hàng trong khoảng thời gian chỉ định.',
            'order_count' => $orderCount,
            'orders' => $orders,
        ]);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
    }
}
/**
     * @OA\Delete(
     *     path="/api/admin/orders/{id}",
     *     tags={"Orders Admin Management"},
     *     summary="Xóa đơn hàng",
     *     security={{"Bearer": {}}},
     *     description="Xóa một đơn hàng bằng cách đánh dấu trường deleted_at.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của đơn hàng",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đơn hàng đã được xóa thành công.",
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Bạn không có quyền xóa đơn hàng.",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy đơn hàng.",
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Không thể xóa đơn hàng có trạng thái này.",
     *     )
     * )
     */
    public function destroy($id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Bạn không có quyền xóa đơn hàng.'], 403);
        }

        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng.'], 404);
        }
        if (!in_array($order->order_status, ['pending', 'cancelled', 'returned_refunded'])) {
            return response()->json(['message' => 'Không thể xóa đơn hàng có trạng thái này.'], 400);
        }
        $order->deleted_at = now();
        $order->save();

        return response()->json(['message' => 'Đơn hàng đã được xóa thành công.'], 200);
    }

}
