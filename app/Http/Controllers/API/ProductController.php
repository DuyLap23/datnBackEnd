<?php

namespace App\Http\Controllers\API;

use App\Models\Tag;
use App\Models\Product;
use App\Models\ProductSize;
use Illuminate\Support\Str;
use App\Models\ProductColor;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
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
        $products = Product::with(
            [
                'category',
                'brand',
                'tags',
                'productImages',
                'productVariants.productColor',
                'productVariants.productSize'
            ]
        )->get();

        return response()->json(
            [
                'success' => true,
                'message' => 'Lấy thành công sản phẩm',
                'products' =>  $products,
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

            // Xử lý thẻ
            if ($request->has('tags')) {
                $product->tags()->sync($request->tags);
            }

            DB::commit();

            return response()->json(
                $product->load(['category', 'brand', 'tags', 'productImages', 'productVariants.productColor', 'productVariants.productSize']),
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
    public function show($id)
    {
        try {
            $product = Product::findOrFail($id); // Tìm sản phẩm dựa trên ID
            $productData = $product->load([
                'category',
                'brand',
                'tags',
                'productImages',
                'productVariants.productColor',
                'productVariants.productSize'
            ]);

            return response()->json($productData, 200);
        } catch (ModelNotFoundException $e) {
            Log::error('Sản phẩm không tìm thấy: ' . $e->getMessage());
            return response()->json([
                'error' => 'Sản phẩm không tìm thấy.',
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

    public function update(Request $request, Product $product)
    {
        DB::beginTransaction();
        try {
            // Kiểm tra và xác thực dữ liệu
            $dataProduct = $request->except(['product_variants', 'tags', 'product_images']);
            $dataProduct = array_merge($dataProduct, [
                'is_active' => $request->input('is_active', 0),
                'is_new' => $request->input('is_new', 0),
                'is_show_home' => $request->input('is_show_home', 0),
                'slug' => Str::slug($dataProduct['name']) . '-' . $product->sku,
            ]);

            // Xử lý hình ảnh thumbnail
            if ($request->hasFile('img_thumbnail')) {
                // Xóa hình ảnh cũ
                Storage::delete($product->img_thumbnail);
                // Lưu hình ảnh mới và tạo URL công khai
                $path = $request->file('img_thumbnail')->store('products', 'public');
                $dataProduct['img_thumbnail'] = asset('storage/' . $path);
            }

            // Cập nhật sản phẩm
            $product->update($dataProduct);

            // Xử lý biến thể sản phẩm
            if ($request->has('product_variants')) {
                $existingVariants = $product->variants()->get()->keyBy(function ($item) {
                    return $item->product_size_id . '-' . $item->product_color_id;
                });

                foreach ($request->product_variants as $key => $value) {
                    $tmp = explode('-', $key);

                    // Kiểm tra nếu $tmp có ít nhất 2 phần
                    if (count($tmp) < 2) {
                        Log::error('Invalid variant key format: ' . $key);
                        continue; // Bỏ qua biến thể này nếu định dạng không hợp lệ
                    }

                    $dataProductVariant = [
                        'product_size_id' => $tmp[0],
                        'product_color_id' => $tmp[1],
                        'quantity' => $value['quantity'] ?? 0,
                        'image' => null,
                    ];

                    $variantKey = $dataProductVariant['product_size_id'] . '-' . $dataProductVariant['product_color_id'];

                    if (isset($existingVariants[$variantKey])) {
                        $variant = $existingVariants[$variantKey];
                        if (isset($value['image']) && $value['image'] instanceof \Illuminate\Http\UploadedFile) {
                            // Xóa hình ảnh cũ
                            Storage::delete($variant->image);
                            // Lưu hình ảnh mới và tạo URL công khai
                            $path = $value['image']->store('products', 'public');
                            $dataProductVariant['image'] = asset('storage/' . $path);
                        } else {
                            $dataProductVariant['image'] = $variant->image; // Giữ nguyên hình ảnh cũ
                        }
                        $variant->update($dataProductVariant);
                        unset($existingVariants[$variantKey]);
                    } else {
                        // Thêm biến thể mới
                        $dataProductVariant['product_id'] = $product->id;
                        if (isset($value['image']) && $value['image'] instanceof \Illuminate\Http\UploadedFile) {
                            $path = $value['image']->store('products', 'public');
                            $dataProductVariant['image'] = asset('storage/' . $path);
                        }
                        ProductVariant::create($dataProductVariant);
                    }
                }

                // Xóa các biến thể không còn tồn tại
                foreach ($existingVariants as $variant) {
                    Storage::delete($variant->image);
                    $variant->delete();
                }
            }

            // Xử lý thẻ
            if ($request->has('tags')) {
                $product->tags()->sync($request->tags);
            }

            DB::commit();

            return response()->json($product->load(['category', 'tags', 'productImages', 'productVariants.productColor', 'productVariants.productSize']));
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

    public function destroy(Product $product)
    {
        DB::beginTransaction();
        try {
            // Xóa hình ảnh thumbnail
            if ($product->img_thumbnail) {
                Storage::delete($product->img_thumbnail);
            }

            // Xóa hình ảnh sản phẩm
            // if ($product->productImages && $product->productImages->isNotEmpty()) {
            //     foreach ($product->productImages as $gallery) {
            //         Storage::delete($gallery->image);
            //         $gallery->delete();
            //     }
            // }

            // Xóa biến thể và hình ảnh của nó
            if ($product->variants && $product->variants->isNotEmpty()) {
                foreach ($product->variants as $variant) {
                    if ($variant->image) {
                        Storage::delete($variant->image);
                    }
                    $variant->delete();
                }
            }

            // Ngắt kết nối thẻ
            $product->tags()->detach();

            // Xóa sản phẩm
            $product->delete();
            DB::commit();
            return response()->json(['message' => 'Xóa sản phẩm thành công'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi xoá sản phẩm: ' . $e->getMessage());
            return response()->json(['error' => 'Lỗi xoá sản phẩm: ' . $e->getMessage()], 500);
        }
    }
}
