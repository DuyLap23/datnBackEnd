<?php

namespace App\Http\Controllers\API\Search;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * @OA\Get(
     *     path="api/search",
     *     summary="Search products by name, category, or brand",
     *     description="Tìm kiếm sản phẩm theo tên, danh mục hoặc thương hiệu. Chỉ cần một input duy nhất để thực hiện tìm kiếm.",
     *     operationId="Search",
     *     tags={"Search"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Từ khóa tìm kiếm cho sản phẩm, danh mục hoặc thương hiệu",
     *         required=false,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách các sản phẩm phù hợp",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Products")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy sản phẩm phù hợp"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server"
     *     )
     * ),
     * @OA\Schema(
     *      schema="Products",
     *      type="object",
     *      required={"id", "name", "slug", "sku", "img_thumbnail", "price_regular", "price_sale", "description", "content", "user_manual", "category_id", "brand_id", "created_at", "updated_at"},
     *      @OA\Property(property="id", type="integer", example=60),
     *      @OA\Property(property="name", type="string", example="Vel quo accusantium magnam nihil. Et mollitia nemo dolores quia commodi. Rerum est beatae at saepe."),
     *      @OA\Property(property="slug", type="string", example="vel-quo-accusantium-magnam-nihil-et-mollitia-nemo-dolores-quia-commodi-rerum-est-beatae-at-saepe-cEbN7UQR"),
     *      @OA\Property(property="sku", type="string", example="02Bmk4Q59"),
     *      @OA\Property(property="img_thumbnail", type="string", example="https://canifa.com/img/1000/1500/resize/8/b/8bj24s003-sj859-31-1-u.webp"),
     *      @OA\Property(property="price_regular", type="string", example="557978.00"),
     *      @OA\Property(property="price_sale", type="string", example="446382.00"),
     *      @OA\Property(property="description", type="string", example="Vel porro quia vero distinctio omnis. Aut qui rem mollitia quis ipsam veritatis qui voluptate. Autem error consequuntur non aperiam."),
     *      @OA\Property(property="content", type="string", example="Fugit quam laudantium qui facere eligendi laborum eveniet quia. Fuga deserunt modi rerum debitis consequatur."),
     *      @OA\Property(property="user_manual", type="string", example="Facere ea esse placeat est consectetur exercitationem a sed. Et modi tenetur voluptatibus doloribus cumque."),
     *      @OA\Property(property="view", type="integer", example=0),
     *      @OA\Property(property="is_active", type="boolean", example=true),
     *      @OA\Property(property="is_new", type="boolean", example=true),
     *      @OA\Property(property="is_show_home", type="boolean", example=true),
     *      @OA\Property(property="category_id", type="integer", example=3),
     *      @OA\Property(property="brand_id", type="integer", example=6),
     *      @OA\Property(property="created_at", type="string", format="date-time", example="2024-10-21T06:30:19.000000Z"),
     *      @OA\Property(property="updated_at", type="string", format="date-time", example="2024-10-21T06:30:19.000000Z"),
     *      @OA\Property(property="deleted_at", type="string", format="date-time", example="null")
     *  )
     */

    public function search(Request $request)
    {
        $search = $request->input('search');

        $products = Product::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhereHas('category', function ($q) use ($search) {
                        $q->where('name', 'LIKE', '%' . $search . '%');
                    })
                    ->orWhereHas('brand', function ($q) use ($search) {
                        $q->where('name', 'LIKE', '%' . $search . '%');
                    });
            })
            ->latest()
            ->get();

        return response()->json($products);
    }
}
