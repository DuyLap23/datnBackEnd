<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
         $products = Product::with(
             [
                 'category',
                 'brand',
                 'tags',
                 'productVariants.productColor',
                 'productVariants.productSize',
                 'comments' // Đảm bảo lấy các bình luận
             ]
         )
         ->where('is_active', 1)
         ->whereNull('deleted_at') // Chỉ lấy các sản phẩm chưa bị soft delete
         ->get();
     
         // Tính trung bình số sao và số lượng đánh giá cho mỗi sản phẩm
         $products->each(function ($product) {
             // Tính tổng số sao và số lượng đánh giá
             $totalRatings = $product->comments->count();
             $averageRating = $totalRatings > 0
                 ? $product->comments->avg('rating') 
                 : 0;
     
             // Thêm vào thuộc tính trung bình sao và số đánh giá cho mỗi sản phẩm
             $product->average_rating = $averageRating;
             $product->total_ratings = $totalRatings;
         });
         return response()->json(
             [
                 'success' => true,
                 'message' => 'Lấy thành công sản phẩm',
                 'products' => $products,
             ],
             200,
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
            $dataProduct['is_new'] = $request->input('is_new', 0);
            $dataProduct['is_show_home'] = $request->input('is_show_home', 0);
            $dataProduct['slug'] = Str::slug($dataProduct['name']) . '-' . Str::uuid();
            $dataProduct['sku'] = Str::uuid();

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
            if (!\Cache::has($cacheKey)) {
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
                'is_new' => $productData->is_new,
                'is_show_home' => $productData->is_show_home,
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
    
        $product = Product::where('slug', $slug)->firstOrFail();

        DB::beginTransaction();
        try {
            $dataProduct = $request->except(['product_variants', 'tags', 'product_images']);
            $dataProduct = array_merge($dataProduct, [
                'is_active' => $request->input('is_active', 0),
                'is_new' => $request->input('is_new', 0),
                'is_show_home' => $request->input('is_show_home', 0),
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
                // Delete existing variants
                $product->productVariants()->delete();

                // Create new variants
                foreach ($request->product_variants as $variant) {
                    $dataProductVariant = [
                        'product_id' => $product->id,
                        'product_size_id' => $variant['product_size_id'],
                        'product_color_id' => $variant['product_color_id'],
                        'quantity' => $variant['quantity'],
                        'image' => null,
                    ];

                    // Image handling for variant
                    if (isset($variant['image']) && $variant['image'] instanceof \Illuminate\Http\UploadedFile) {
                        $path = $variant['image']->store('products', 'public');
                        $dataProductVariant['image'] = asset('storage/' . $path);
                    }

                    ProductVariant::updateOrCreate($dataProductVariant);
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
            $productImagePath = str_replace(url('/storage') . '/', '', $product->img_thumbnail);
            if ($product->img_thumbnail && Storage::exists($productImagePath)) {
                Storage::delete($productImagePath);
                Log::info('Xoá ảnh sản phẩm thành công.');
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


    public function searchProduct(Request $request)
{
    try {
        $keyword = $request->input('keyword');
        
        if (empty($keyword)) {
            return response()->json([
                'message' => 'Từ khóa tìm kiếm không được để trống',
                'data' => []
            ], 400);
        }

        // Tìm trong cả sản phẩm đã xóa và chưa xóa
        $products = Product::withTrashed()
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'LIKE', "%{$keyword}%")
                      ->orWhere('slug', 'LIKE', "%{$keyword}%");
            })
            ->with(['category', 'brand', 'productImages', 'productVariants.productColor', 'productVariants.productSize'])
            ->get();

        // Phân loại kết quả
        $activeProducts = $products->whereNull('deleted_at');
        $deletedProducts = $products->whereNotNull('deleted_at');

        // Chuẩn bị response
        $response = [
            'message' => 'Tìm kiếm sản phẩm thành công',
            'data' => $activeProducts,
            'total_active' => $activeProducts->count()
        ];

        // Thêm thông tin về sản phẩm đã xóa nếu có
        if ($deletedProducts->count() > 0) {
            $response['deleted_products'] = [
                'message' => 'Sản phẩm đã bị xóa trước đó',
                'data' => $deletedProducts->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'deleted_at' => $product->deleted_at->format('d/m/Y H:i:s')
                    ];
                }),
                'total_deleted' => $deletedProducts->count()
            ];
        }

        return response()->json($response, 200);

    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return response()->json([
            'message' => 'Lỗi khi tìm kiếm sản phẩm',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
