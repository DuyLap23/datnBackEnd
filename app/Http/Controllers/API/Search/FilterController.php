<?php

namespace App\Http\Controllers\API\Search;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FilterController extends Controller
{
    public function filter(Request $request)
    {
        $categories = $request->input('categories', []);
        $brands = $request->input('brands', []);

        if (!empty($categories) && !is_array($categories)) {
            $categories = [$categories];
        }

        if (!empty($brands) && !is_array($brands)) {
            $brands = [$brands];
        }
        // Xác thực dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'categories' => 'nullable',
            'brands' => 'nullable',
            'minPrice' => 'nullable|numeric|min:0',
            'maxPrice' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
//        $data = $request->all();
//        dd($data);
        // Lấy các tham số từ request
        $categories = $request->input('categories'); // Mảng danh mục
        $brands = $request->input('brands'); // Mảng thương hiệu
        $minPrice = $request->input('minPrice', 0); // Giá tối thiểu
        $maxPrice = $request->input('maxPrice', PHP_INT_MAX); // Giá tối đa

        // Kiểm tra xem minPrice có lớn hơn maxPrice không
        if ($minPrice > $maxPrice) {
            return response()->json(['error' => 'Giá tối thiểu phải nhỏ hơn giá tối đa'], 400);
        }

        // Truy vấn sản phẩm dựa trên các tham số
        $products = Product::query();

        if ($minPrice >= 0 && $maxPrice >= 0  && $minPrice <= $maxPrice) {
            // Kiểm tra xem có giá trị price_sale hay không
            $products->where(function ($query) use ($minPrice, $maxPrice) {
                $query->where('price_sale', '!=', null)
                    ->whereBetween('price_sale', [$minPrice, $maxPrice])
                    ->orWhere(function ($subQuery) use ($minPrice, $maxPrice) {
                        $subQuery->where('price_sale', null)
                            ->whereBetween('price_regular', [$minPrice, $maxPrice]);
                    });
            });
        }

        if (!empty($categories)) {
           $products->whereIn('category_id', $categories);
        }

        if (!empty($brands)) {
            $products->whereIn('brand_id', $brands);
        }

        $products = $products->get();
        dd($products->toArray());

        return response()->json($products);
    }
}
