<?php

namespace App\Http\Controllers\API;

use Cache;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/products",
     *     summary="Lấy danh sách sản phẩm",
     *     description="Trả về danh sách tất cả các sản phẩm cùng với các thông tin liên quan.",
     *     tags={"Product"},
     *     @OA\Response(
     *         response=200,
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true,
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Lấy thành công sản phẩm",
     *             ),
     *             @OA\Property(
     *                 property="products",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1,
     *                     ),
     *                     @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="Product Name",
     *                     ),
     *                     @OA\Property(
     *                         property="description",
     *                         type="string",
     *                         example="Mô tả sản phẩm.",
     *                     ),
     *                     @OA\Property(
     *                         property="price",
     *                         type="number",
     *                         format="float",
     *                         example=99.99,
     *                     ),
     *                     @OA\Property(
     *                         property="category",
     *                         type="object",
     *                         @OA\Property(
     *                             property="id",
     *                             type="integer",
     *                             example=2,
     *                         ),
     *                         @OA\Property(
     *                             property="name",
     *                             type="string",
     *                             example="Category Name",
     *                         ),
     *                     ),
     *                     @OA\Property(
     *                         property="brand",
     *                         type="object",
     *                         @OA\Property(
     *                             property="id",
     *                             type="integer",
     *                             example=1,
     *                         ),
     *                         @OA\Property(
     *                             property="name",
     *                             type="string",
     *                             example="Brand Name",
     *                         ),
     *                     ),
     *                     @OA\Property(
     *                         property="tags",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(
     *                                 property="id",
     *                                 type="integer",
     *                                 example=1,
     *                             ),
     *                             @OA\Property(
     *                                 property="name",
     *                                 type="string",
     *                                 example="Tag Name",
     *                             ),
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="productImages",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(
     *                                 property="url",
     *                                 type="string",
     *                                 example="https://picsum.photos/200/300?random=1",
     *                             ),
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="productVariants",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(
     *                                 property="id",
     *                                 type="integer",
     *                                 example=1,
     *                             ),
     *                             @OA\Property(
     *                                 property="price",
     *                                 type="number",
     *                                 format="float",
     *                                 example=99.99,
     *                             ),
     *                             @OA\Property(
     *                                 property="productColor",
     *                                 type="object",
     *                                 @OA\Property(
     *                                     property="id",
     *                                     type="integer",
     *                                     example=1,
     *                                 ),
     *                                 @OA\Property(
     *                                     property="name",
     *                                     type="string",
     *                                     example="Red",
     *                                 ),
     *                             ),
     *                             @OA\Property(
     *                                 property="productSize",
     *                                 type="object",
     *                                 @OA\Property(
     *                                     property="id",
     *                                     type="integer",
     *                                     example=1,
     *                                 ),
     *                                 @OA\Property(
     *                                     property="name",
     *                                     type="string",
     *                                     example="L",
     *                                 ),
     *                             ),
     *                         ),
     *                     ),
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=false,
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Lỗi khi lấy danh sách sản phẩm.",
     *             ),
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Server Error Message",
     *             ),
     *         )
     *     )
     * )
     */


    public function index()
    {
        $result = [];
        $startTime = microtime(true);
        Product::with(
            [
                'category',
                // 'brand',
                // 'tags',
                // 'productVariants.productColor',
                // 'productVariants.productSize',
                'comments'
            ]
        )
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->chunk(10, function ($products) use (&$result) {

                $products->each(function ($product) {

                    $totalRatings = $product->comments->count();
                    $averageRating = $totalRatings > 0
                        ? $product->comments->avg('rating')
                        : 0;

                    $product->average_rating = $averageRating;
                    $product->total_ratings = $totalRatings;
                });

                // Lưu các sản phẩm đã được xử lý vào mảng kết quả
                $result = array_merge($result, $products->toArray());
            });
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        // Trả về kết quả dưới dạng JSON
        return response()->json(
            [
                'success' => true,
                'message' => 'Lấy thành công sản phẩm',
                'products' => $result,
                'execution_time' => number_format($executionTime, 5)
            ],
            200
        );
    }




    /**
     * @OA\Post(
     *     path="/api/admin/products",
     *     summary="Thêm sản phẩm mới",
     *     description="Thêm sản phẩm vào hệ thống với các biến thể và thẻ.",
     *     tags={"Product"},
     *     security={{"Bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="Tên sản phẩm",
     *                     example="Sản phẩm A"
     *                 ),
     *                 @OA\Property(
     *                     property="price_regular",
     *                     type="number",
     *                     format="float",
     *                     description="Giá sản phẩm",
     *                     example=199000
     *                 ),
     *                  @OA\Property(
     *                     property="price_sale",
     *                     type="number",
     *                     format="float",
     *                     description="Giá khuyến mãi",
     *                     example=199000
     *                 ),
     *                  @OA\Property(
     *                     property="content",
     *                     type="boolean",
     *                     description="Mô tả sản phẩm",
     *                     example=true
     *                 ),
     *                  @OA\Property(
     *                     property="description",
     *                     type="boolean",
     *                     description="Mô tả ngắn sản phẩm",
     *                     example=true
     *                 ),
     *                   @OA\Property(
     *                     property="view",
     *                     type="boolean",
     *                     description="Lượng người truy cập sản phẩm",
     *                     example=true
     *                 ),
     *
     *                 @OA\Property(
     *                     property="user_manual",
     *                     type="boolean",
     *                     description="Chất liệu của sản phẩm",
     *                     example=true
     *                 ),
     *                 @OA\Property(
     *                     property="is_active",
     *                     type="boolean",
     *                     description="Trạng thái hoạt động của sản phẩm",
     *                     example=true
     *                 ),
     *                 @OA\Property(
     *                     property="is_new",
     *                     type="boolean",
     *                     description="Sản phẩm mới",
     *                     example=true
     *                 ),
     *                 @OA\Property(
     *                     property="is_show_home",
     *                     type="boolean",
     *                     description="Hiển thị trên trang chủ",
     *                     example=false
     *                 ),
     *                 @OA\Property(
     *                     property="img_thumbnail",
     *                     type="string",
     *                     format="binary",
     *                     description="Ảnh thumbnail của sản phẩm",
     *                     example="thumbnail.png"
     *                 ),
     *                 @OA\Property(
     *                     property="product_variants",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(
     *                             property="product_size_id",
     *                             type="integer",
     *                             description="ID kích thước sản phẩm",
     *                             example=1
     *                         ),
     *                         @OA\Property(
     *                             property="product_color_id",
     *                             type="integer",
     *                             description="ID màu sắc sản phẩm",
     *                             example=2
     *                         ),
     *                         @OA\Property(
     *                             property="quantity",
     *                             type="integer",
     *                             description="Số lượng biến thể",
     *                             example=10
     *                         ),
     *                         @OA\Property(
     *                             property="image",
     *                             type="string",
     *                             format="binary",
     *                             description="Hình ảnh biến thể sản phẩm",
     *                             example="variant_image.png"
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="tags",
     *                     type="array",
     *                     @OA\Items(
     *                         type="integer",
     *                         description="ID thẻ liên kết với sản phẩm",
     *                         example=1
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Thêm sản phẩm thành công",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="id",
     *                 type="integer",
     *                 example=1
     *             ),
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="Sản phẩm A"
     *             ),
     *             @OA\Property(
     *                 property="price",
     *                 type="number",
     *                 format="float",
     *                 example=199000
     *             ),
     *             @OA\Property(
     *                 property="category",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="Danh mục 1"
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="brand",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="Thương hiệu A"
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="tags",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="Thẻ 1"
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="product_images",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(
     *                         property="url",
     *                         type="string",
     *                         example="https://apitopdeal.shop/storage/products/thumbnail.png"
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="product_variants",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(
     *                         property="product_size",
     *                         type="object",
     *                         @OA\Property(
     *                             property="name",
     *                             type="string",
     *                             example="Size M"
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="product_color",
     *                         type="object",
     *                         @OA\Property(
     *                             property="name",
     *                             type="string",
     *                             example="Màu Đỏ"
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="quantity",
     *                         type="integer",
     *                         example=10
     *                     ),
     *                     @OA\Property(
     *                         property="image_url",
     *                         type="string",
     *                         example="https://apitopdeal.shop/storage/products/variant_image.png"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Thêm sản phẩm thất bại",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Lỗi thêm sản phẩm: {error details}"
     *             )
     *         )
     *     )
     * )
     */

    public function store(ProductRequest $request)
    {
        $currentUser = auth('api')->user();
        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không phải admin.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Lấy dữ liệu sản phẩm và gán các giá trị mặc định
            $dataProduct = $request->except(['product_variants', 'tags', 'product_images']);
            $dataProduct['is_active'] = $request->input('is_active', 0);
            // $dataProduct['is_new'] = $request->input('is_new', 0);
            // $dataProduct['is_show_home'] = $request->input('is_show_home', 0);
            $dataProduct['slug'] = Str::slug($dataProduct['name']) . '-' . Str::uuid();
            // $dataProduct['sku'] = Str::uuid();

            // Xử lý hình ảnh thumbnail
            if ($request->hasFile('img_thumbnail')) {
                $path = $request->file('img_thumbnail')->store('products', 'public');
                $dataProduct['img_thumbnail'] = asset('storage/' . $path);
            }
            $category = Category::find($request->category_id);
            if ($category && $category->parent_id === 0) {
                return response()->json([
                    'error' => 'Danh mục phải là danh mục con.'
                ]);
            }
            // Tạo sản phẩm
            $product = Product::create($dataProduct);
            // Validate khi xử lý product_variants
            if ($request->has('product_variants')) {
                $variants = collect($request->product_variants);
                $duplicates = $variants->duplicates(function ($variant) {
                    return $variant['product_size_id'] . '-' . $variant['product_color_id'];
                });

                if ($duplicates->isNotEmpty()) {
                    return response()->json([
                        'error' => 'Không được phép có biến thể trùng lặp (size và color giống nhau)'
                    ], 400);
                }
            }

            // Xử lý biến thể sản phẩm
            if ($request->has('product_variants')) {
                foreach ($request->product_variants as $variant) {
                    $dataProductVariant = [
                        'product_id' => $product->id,
                        'product_size_id' => $variant['product_size_id'],
                        'product_color_id' => $variant['product_color_id'],
                        'quantity' => $variant['quantity'],
                        'image' => null, // Khởi tạo hình ảnh là null
                    ];

                    // Xử lý hình ảnh cho biến thể
                    if (isset($variant['image']) && $variant['image'] instanceof \Illuminate\Http\UploadedFile) {
                        $path = $variant['image']->store('products', 'public');
                        $dataProductVariant['image'] = asset('storage/' . $path);
                    }

                    // Tạo biến thể sản phẩm
                    ProductVariant::create($dataProductVariant);
                }
            }


            DB::commit();

            return response()->json(
                $product->load(['category', 'brand', 'productImages', 'productVariants.productColor', 'productVariants.productSize']),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => 'Lỗi thêm sản phẩm: ' . $e->getMessage()], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/admin/product/{id}",
     *     summary="Lấy chi tiết sản phẩm",
     *     tags={"Product"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của sản phẩm",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy thành công dữ liệu của sản phẩm",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy thành công dữ liệu của sản phẩm {id}"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Tên sản phẩm"),
     *                 @OA\Property(property="img_thumbnail", type="string", example="thumbnail.png"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="is_new", type="boolean", example=false),
     *                 @OA\Property(property="is_show_home", type="boolean", example=true),
     *                 @OA\Property(property="category", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Danh mục sản phẩm")
     *                 ),
     *                 @OA\Property(property="brand", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Thương hiệu sản phẩm")
     *                 ),
     *                 @OA\Property(property="tags", type="array",
     *                     @OA\Items(type="string", example="Tag 1")
     *                 ),
     *                 @OA\Property(property="productImages", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="image_url", type="string", example="image1.png")
     *                     )
     *                 ),
     *                 @OA\Property(property="productVariants", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="product_size_id", type="integer", example=1),
     *                         @OA\Property(property="product_color_id", type="integer", example=2),
     *                         @OA\Property(property="quantity", type="integer", example=10),
     *                         @OA\Property(property="image", type="string", example="variant_image.png")
     *                     )
     *                 )
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sản phẩm không tìm thấy",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Sản phẩm không tìm thấy."),
     *             @OA\Property(property="error", type="string", example="No query results for model [Product]"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lỗi khi lấy thông tin sản phẩm."),
     *             @OA\Property(property="error", type="string", example="Server Error Message"),
     *         )
     *     )
     * )
     */
    public function show($slug)
    {
        try {
            $product = Product::where('slug', $slug)->firstOrFail();

            // Xử lí tăng view
            $userIp = request()->ip();
            $cacheKey = "product_view_{$product->id}_{$userIp}";

            // Kiểm tra xem IP này đã tăng view trong 10 phút chưa
            if (!Cache::has($cacheKey)) {
                $product->increment('view'); // Tăng view
                \Cache::put($cacheKey, true, now()->addMinutes(2)); // Lưu vào cache 2 phút
            }

            $productData = $product->load([
                'tags',
                'productVariants.productColor',
                'productVariants.productSize'
            ]);

            // Lấy tên category và brand
            $productData->category_name = $product->category->name ?? null;
            $productData->brand_name = $product->brand->name ?? null;

            // Lấy sản phẩm liên quan
            $relatedProducts = Product::where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->where('is_active', 1)
                ->limit(20)
                ->get()
                ->map(function ($relatedProduct) {
                    return [
                        'id' => $relatedProduct->id,
                        'name' => $relatedProduct->name,
                        'slug' => $relatedProduct->slug,
                        'img_thumbnail' => $relatedProduct->img_thumbnail,
                        'price_regular' => $relatedProduct->price_regular,
                        'price_sale' => $relatedProduct->price_sale,
                    ];
                });

            // Chuẩn bị kết quả trả về
            $result = [
                'id' => $productData->id,
                'name' => $productData->name,
                'category_name' => $productData->category_name,
                'brand_name' => $productData->brand_name,
                'img_thumbnail' => $productData->img_thumbnail,
                'is_active' => $productData->is_active,
                'description' => $productData->description,
                'content' => $productData->content,
                'view' => $productData->view,
                'user_manual' => $productData->user_manual,
                'price_regular' => $productData->price_regular,
                'price_sale' => $productData->price_sale,
                'tags' => $productData->tags,
                'productImages' => $productData->productImages,
                'productVariants' => $productData->productVariants,
                'delete_at' => $productData->delete_at,
                'related_products' => $relatedProducts, // Thêm sản phẩm liên quan
            ];

            return response()->json($result, 200);
        } catch (ModelNotFoundException $e) {
            Log::error('Sản phẩm không tìm thấy: ' . $e->getMessage());
            return response()->json([
                'error' => 'Không tìm thấy sản phẩm.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy thông tin sản phẩm: ' . $e->getMessage());
            return response()->json([
                'error' => 'Lỗi khi lấy thông tin sản phẩm. Vui lòng thử lại sau.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/product/{id}",
     *     summary="Cập nhật thông tin sản phẩm",
     *     tags={"Product"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của sản phẩm",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Tên sản phẩm"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="is_new", type="boolean", example=false),
     *             @OA\Property(property="is_show_home", type="boolean", example=true),
     *             @OA\Property(property="img_thumbnail", type="string", format="binary", example="thumbnail.png"),
     *             @OA\Property(property="product_variants", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="product_size_id", type="integer", example=1),
     *                     @OA\Property(property="product_color_id", type="integer", example=2),
     *                     @OA\Property(property="quantity", type="integer", example=10),
     *                     @OA\Property(property="image", type="string", format="binary", example="variant_image.png"),
     *                     @OA\Property(property="old_image", type="string", example="old_image.png")
     *                 )
     *             ),
     *             @OA\Property(property="tags", type="array",
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật thành công thông tin sản phẩm",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cập nhật thành công sản phẩm {id}"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Tên sản phẩm"),
     *                 @OA\Property(property="img_thumbnail", type="string", example="thumbnail.png"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="is_new", type="boolean", example=false),
     *                 @OA\Property(property="is_show_home", type="boolean", example=true),
     *                 @OA\Property(property="tags", type="array",
     *                     @OA\Items(type="integer", example=1)
     *                 ),
     *                 @OA\Property(property="productVariants", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="product_size_id", type="integer", example=1),
     *                         @OA\Property(property="product_color_id", type="integer", example=2),
     *                         @OA\Property(property="quantity", type="integer", example=10),
     *                         @OA\Property(property="image", type="string", example="variant_image.png"),
     *                     )
     *                 )
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sản phẩm không tìm thấy",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cập nhật không thành công"),
     *             @OA\Property(property="error", type="string", example="No query results for model [Product]"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cập nhật không thành công"),
     *             @OA\Property(property="error", type="string", example="Server Error Message"),
     *         )
     *     )
     * )
     */


    public function update(Request $request, $slug)
    {
        $currentUser = auth('api')->user();
        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không phải admin.'
            ], 403);
        }

        //validte biến thể
        if ($request->has('product_variants')) {
            $variants = $request->product_variants;
            
            // Kiểm tra trùng lặp biến thể
            $variantKeys = [];
            foreach ($variants as $index => $variant) {
                $key = $variant['product_size_id'] . '-' . $variant['product_color_id'];
                
                if (in_array($key, $variantKeys)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể tạo biến thể trùng lặp (Size và Color giống nhau).'
                    ], status: 422);
                }
                
                $variantKeys[] = $key;
            }
        }

        $product = Product::where('slug', $slug)->firstOrFail();

        DB::beginTransaction();
        try {
            $dataProduct = $request->except(['product_variants', 'tags', 'product_images']);
            $dataProduct = array_merge($dataProduct, [
                'is_active' => $request->input('is_active', 0),
                'slug' => Str::slug($dataProduct['name']) . '-' . $product->sku,
            ]);

            // Thumbnail handling
            if ($request->hasFile('img_thumbnail')) {
                if ($product->img_thumbnail) {
                    Storage::delete(str_replace(asset('storage/'), '', $product->img_thumbnail));
                }
                $path = $request->file('img_thumbnail')->store('products', 'public');
                $dataProduct['img_thumbnail'] = asset('storage/' . $path);
            }

            $product->update($dataProduct);

            // Product Variants Handling
            if ($request->has('product_variants')) {
                foreach ($request->product_variants as $variant) {
                    $existingVariant = ProductVariant::where([
                        'product_id' => $product->id,
                        'product_size_id' => $variant['product_size_id'],
                        'product_color_id' => $variant['product_color_id'],
                    ])->first();

                    $dataProductVariant = [
                        'product_id' => $product->id,
                        'product_size_id' => $variant['product_size_id'],
                        'product_color_id' => $variant['product_color_id'],
                        'quantity' => $variant['quantity'],
                    ];

                    // Xử lý ảnh
                    if (isset($variant['image']) && $variant['image'] instanceof \Illuminate\Http\UploadedFile) {
                        // Nếu có ảnh mới upload
                        $path = $variant['image']->store('products', 'public');
                        $dataProductVariant['image'] = asset('storage/' . $path);

                        // Xóa ảnh cũ nếu tồn tại
                        if ($existingVariant && $existingVariant->image) {
                            Storage::delete(str_replace(asset('storage/'), '', $existingVariant->image));
                        }
                    } else {
                        // Nếu không có ảnh được gửi lên, set image là null và xóa ảnh cũ
                        $dataProductVariant['image'] = null;

                        // Xóa file ảnh cũ nếu tồn tại
                        if ($existingVariant && $existingVariant->image) {
                            Storage::delete(str_replace(asset('storage/'), '', $existingVariant->image));
                        }
                    }

                    ProductVariant::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'product_size_id' => $variant['product_size_id'],
                            'product_color_id' => $variant['product_color_id'],
                        ],
                        $dataProductVariant
                    );
                }
            }

            DB::commit();

            $productWithVariants = Product::with([
                'category',
                'brand',
                'productImages',
                'productVariants.productColor',
                'productVariants.productSize'
            ])->find($product->id);

            return response()->json([
                'data' => $productWithVariants,
                'message' => 'Cập nhật sản phẩm thành công.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi chỉnh sửa sản phẩm: ' . $e->getMessage());
            return response()->json(['error' => 'Lỗi chỉnh sửa sản phẩm: ' . $e->getMessage()], 500);
        }
    }


    /**
     * @OA\Delete(
     *     path="/api/admin/products/{id}",
     *     summary="Xóa sản phẩm",
     *     tags={"Product"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của sản phẩm cần xóa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Xóa sản phẩm thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Xóa sản phẩm thành công.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi khi xóa sản phẩm",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Xóa sản phẩm không thành công."),
     *             @OA\Property(property="error", type="string", example="Lý do lỗi")
     *         )
     *     )
     * )
     */

    public function destroy(string $id)
    {
        $currentUser = auth('api')->user();
        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không phải admin.'
            ], 403);
        }

        $currentUser = auth('api')->user();
        if (!$currentUser) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa đăng nhập.',
            ], 401);
        }

        if (!$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không phải admin.',
            ], 403);
        }

        try {
            DB::beginTransaction();
            $product = Product::findOrFail($id);
            $productVariants = $product->productVariants()->get();

            if ($productVariants->isEmpty()) {
                Log::warning('Không có biến thể nào để xoá.');
            } else {
                $productVariants->each(function ($variant) {
                    $imagePath = str_replace(url('/storage') . '/', '', $variant->image);
                    if ($variant->image && Storage::exists($imagePath)) {
                        Storage::delete($imagePath);
                        Log::info('Xoá ảnh biến thể thành công.');
                    }
                    $variant->delete();
                    Log::info('Xoá biến thể thành công.');
                });
            }
            $product->delete();
            Log::info('Xoá sản phẩm thành công.');

            DB::commit();

            return response()->json([
                'message' => 'Xóa sản phẩm thành công',
                'deleted' => true
            ], 200);
        } catch (ModelNotFoundException $e) { // Xử lý ngoại lệ cho findOrFail
            DB::rollBack();
            Log::warning('Sản phẩm không tồn tại hoặc đã bị xoá.');
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tồn tại hoặc đã bị xoá.'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi xoá sản phẩm: ' . $e->getMessage());

            return response()->json(['error' => 'Lỗi xoá sản phẩm: ' . $e->getMessage()], 500);
        }
    }


    public function toggleActive(Product $id)
    {
        $id->is_active = !$id->is_active;
        $id->save();

        return response()->json([
            'message' => 'Thay đổi trạng thái sản phẩm thành công',
            'is_active' => $id->is_active,
        ]);
    }
    /**
     * @OA\Get(
     *     path="/api/admin/products/search",
     *     summary="Lấy danh sách hoặc tìm kiếm sản phẩm",
     *     description="Lấy tất cả sản phẩm theo thứ tự mới nhất hoặc tìm kiếm sản phẩm theo từ khóa (nếu có). Admin có thể xem cả sản phẩm đã xóa khi tìm kiếm.",
     *     tags={"Product"},
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         description="Từ khóa tìm kiếm (tên hoặc slug của sản phẩm). Không bắt buộc, nếu không có sẽ trả về tất cả sản phẩm.",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Lấy thành công sản phẩm",
     *             ),
     *             @OA\Property(
     *                 property="products",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1,
     *                     ),
     *                     @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="Product Name",
     *                     ),
     *                     @OA\Property(
     *                         property="description",
     *                         type="string",
     *                         example="Mô tả sản phẩm.",
     *                     ),
     *                     @OA\Property(
     *                         property="price",
     *                         type="number",
     *                         format="float",
     *                         example=99.99,
     *                     ),
     *                     @OA\Property(
     *                         property="category",
     *                         type="object",
     *                         @OA\Property(
     *                             property="id",
     *                             type="integer",
     *                             example=2,
     *                         ),
     *                         @OA\Property(
     *                             property="name",
     *                             type="string",
     *                             example="Category Name",
     *                         ),
     *                     ),
     *                     @OA\Property(
     *                         property="average_rating",
     *                         type="number",
     *                         format="float",
     *                         example=4.5,
     *                     ),
     *                     @OA\Property(
     *                         property="total_ratings",
     *                         type="integer",
     *                         example=10,
     *                     ),
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="deleted_products",
     *                 type="object",
     *                 description="Chỉ hiển thị khi tìm kiếm với quyền admin",
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     example="Sản phẩm đã bị xóa trước đó",
     *                 ),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(
     *                             property="id",
     *                             type="integer",
     *                             example=1,
     *                         ),
     *                         @OA\Property(
     *                             property="name",
     *                             type="string",
     *                             example="Deleted Product Name",
     *                         ),
     *                         @OA\Property(
     *                             property="deleted_at",
     *                             type="string",
     *                             example="30/11/2024 15:30:00",
     *                         ),
     *                     ),
     *                 ),
     *                 @OA\Property(
     *                     property="total_deleted",
     *                     type="integer",
     *                     example=1,
     *                 ),
     *             ),
     *             @OA\Property(
     *                 property="execution_time",
     *                 type="string",
     *                 example="0.12345"
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Không có quyền truy cập",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Bạn không phải admin.",
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Lỗi khi xử lý sản phẩm",
     *             ),
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Server Error Message",
     *             ),
     *         )
     *     )
     * )
     */
    public function searchProduct(Request $request)
    {
        $currentUser = auth('api')->user();
        $search = $request->input('search');
        $result = [];
        $startTime = microtime(true);

        try {
            $query = Product::with('category'); // Chỉ tải category

            // Nếu có từ khóa tìm kiếm và người dùng là admin
            if ($search && $currentUser && $currentUser->isAdmin()) {
                $query->withTrashed()
                    ->when($search, function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('slug', 'LIKE', "%{$search}%");
                    });
            } else {
                // Nếu không có tìm kiếm hoặc không phải admin
                $query->where('is_active', 1)
                    ->whereNull('deleted_at')
                    ->when($search, function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('slug', 'LIKE', "%{$search}%");
                    })
                    ->orderBy('created_at', 'desc');
            }

            $query->chunk(10, function ($products) use (&$result) {
                $result = array_merge($result, $products->toArray());
            });

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            // Nếu đang tìm kiếm và là admin, phân loại kết quả
            if ($search && $currentUser && $currentUser->isAdmin()) {
                $productsCollection = collect($result);
                $activeProducts = $productsCollection->whereNull('deleted_at');
                $deletedProducts = $productsCollection->whereNotNull('deleted_at');

                $response = [
                    'success' => true,
                    'message' => 'Tìm kiếm sản phẩm thành công',
                    'products' => $activeProducts->values()->all(),
                    'total_active' => $activeProducts->count(),
                    'execution_time' => number_format($executionTime, 5)
                ];

                if ($deletedProducts->count() > 0) {
                    $response['deleted_products'] = [
                        'message' => 'Sản phẩm đã bị xóa trước đó',
                        'data' => $deletedProducts->map(function ($product) {
                            return [
                                'id' => $product['id'],
                                'name' => $product['name'],
                                'deleted_at' => Carbon::parse($product['deleted_at'])->format('d/m/Y H:i:s')
                            ];
                        })->values()->all(),
                        'total_deleted' => $deletedProducts->count()
                    ];
                }

                return response()->json($response, 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lấy thành công sản phẩm',
                'products' => $result,
                'execution_time' => number_format($executionTime, 5)
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xử lý sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
