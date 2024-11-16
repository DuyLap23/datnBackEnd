<?php
namespace App\Http\Controllers\API\Statistical;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductStatisticalController extends Controller
{
    public function product(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        // Tính tổng doanh thu của tất cả các sản phẩm trong khoảng thời gian
        $totalRevenue = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.order_status', 'completed')
            ->sum(DB::raw('order_items.quantity * order_items.price'));

        // Định dạng số tiền theo định dạng tiền tệ


        // Lấy top sản phẩm bán chạy nhất
        $topProducts = OrderItem::query()
            ->select(
                'products.id',
                'products.name',
                'products.price_regular as price',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.quantity * order_items.price) as total_revenue')
            )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.order_status', 'completed')
            ->groupBy('products.id', 'products.name', 'products.price_regular')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($product) use ($totalRevenue) {
                return [
                    'id' => $product->id,
                    'name' => Str::limit( $product->name,20),
                    // Định dạng giá
                    'price' => $product->price, 'VND',
                    'total_quantity' =>$product->total_quantity,
                    'total_revenue' => $product->total_revenue, 'VND',
                    'revenue_percentage' => $totalRevenue > 0
                        ? number_format(($product->total_revenue / $totalRevenue) * 100, 2)
                        : 0
                ];
            });

        // Ghi log để kiểm tra giá trị của các biến
        Log::info('Product report generated', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'totalRevenue' => $totalRevenue,
            'topProducts' => $topProducts
        ]);

        return response()->json($topProducts);
    }
}
