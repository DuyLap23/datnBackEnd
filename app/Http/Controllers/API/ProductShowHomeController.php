<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductShowHomeController extends Controller
{
    public function topSellingProducts()
    {
        $topSellingProducts = DB::table('order_items')
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.img_thumbnail as product_image', // Assuming you have an `image` column
                'products.price_regular as product_price_regular',
                'products.price_sale as product_price_sale',
                DB::raw('SUM(order_items.quantity) as total_quantity')
            )
            ->join('orders', 'order_items.order_id', '=', 'orders.id') // Join orders table
            ->join('products', 'order_items.product_id', '=', 'products.id') // Join products table
            ->where('orders.order_status', 'completed') // Filter by order status
            ->groupBy('products.id', 'products.name', 'products.img_thumbnail', 'products.price_regular', 'products.price_sale') // Group by product details
            ->orderByDesc('total_quantity')
            ->take(10) // Limit to top 10 products
            ->get();

        return response()->json([
            'success' => true,
            'data' => $topSellingProducts,
        ]);
    }

    public function getTopViewedProducts($limit = 10)
{
    $products = Product::select(['id', 'name', 'slug', 'img_thumbnail', 'price_regular', 'price_sale','view'])
        ->where('is_active', 1)
        ->where('view', '>', 0)
        ->orderBy('view', 'desc') // Sắp xếp theo lượt view giảm dần
        ->take($limit) // Giới hạn số lượng sản phẩm lấy ra
        ->get();
    if(empty($products)){
        return response()->json([], 200);
    }
    return response()->json($products, 200);
}
}
