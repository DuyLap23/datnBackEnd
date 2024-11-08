<?php

namespace App\Http\Controllers\API\Search;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FilterController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/filter",
     *     summary="Lọc sản phẩm theo các tiêu chí",
     *     tags={"Filter"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="categories", type="array", @OA\Items(type="integer"), description="Danh mục sản phẩm cần lọc"),
     *             @OA\Property(property="brands", type="array", @OA\Items(type="integer"), description="Danh sách thương hiệu cần lọc"),
     *             @OA\Property(property="minPrice", type="integer", example=1000000, description="Giá tối thiểu để lọc"),
     *             @OA\Property(property="maxPrice", type="integer", example=5000000, description="Giá tối đa để lọc")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách sản phẩm sau khi lọc",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", description="ID sản phẩm"),
     *                 @OA\Property(property="name", type="string", description="Tên sản phẩm"),
     *                 @OA\Property(property="price_sale", type="integer", description="Giá khuyến mãi của sản phẩm"),
     *                 @OA\Property(property="price_regular", type="integer", description="Giá gốc của sản phẩm"),
     *                 @OA\Property(property="category_id", type="integer", description="ID danh mục của sản phẩm"),
     *                 @OA\Property(property="brand_id", type="integer", description="ID thương hiệu của sản phẩm"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", description="Thời gian tạo sản phẩm"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", description="Thời gian cập nhật sản phẩm")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Lỗi khi giá tối thiểu lớn hơn giá tối đa",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Giá tối thiểu phải nhỏ hơn giá tối đa")
     *         )
     *     )
     * )
     */
    public function filter(Request $request)
    {

        // Lấy giá trị từ request
        $categories = $request->input('categories', []);
        $brands = $request->input('brands', []);
        $minPrice = $request->input('minPrice', 0);
        $maxPrice = $request->input('maxPrice', PHP_INT_MAX);

        Log::info('Categories:', ['categories' => $categories]);
        Log::info('Brands:', ['brands' => $brands]);
        Log::info('minPrice:', ['minPrice' => $minPrice]);
        Log::info('maxPrice:', ['maxPrice' => $maxPrice]);

        // Kiểm tra giá trị minPrice và maxPrice
        if ($minPrice > $maxPrice) {
            return response()->json(['error' => 'Giá tối thiểu phải nhỏ hơn giá tối đa'], 400);
        }

        // Truy vấn sản phẩm
        $products = Product::query();

        // Áp dụng bộ lọc theo giá nếu có
        if ($minPrice >= 0 && $maxPrice >= 0 && $minPrice <= $maxPrice) {
            $products->where(function ($query) use ($minPrice, $maxPrice) {
                $query->where('price_sale', '!=', null)
                    ->whereBetween('price_sale', [$minPrice, $maxPrice])
                    ->orWhere(function ($subQuery) use ($minPrice, $maxPrice) {
                        $subQuery->where('price_sale', null)
                            ->whereBetween('price_regular', [$minPrice, $maxPrice]);
                    });
            });
        }

        // Áp dụng bộ lọc theo danh mục nếu có
        if (!empty($categories)) {
            $products->whereIn('category_id', $categories);
        }

        // Áp dụng bộ lọc theo thương hiệu nếu có
        if (!empty($brands)) {
            $products->whereIn('brand_id', $brands);
        }

        // Thực hiện truy vấn và trả về kết quả
        $products = $products->get();
        Log::info('productFIlter : ', ['products' => $products]);

        return response()->json($products);
    }
}
