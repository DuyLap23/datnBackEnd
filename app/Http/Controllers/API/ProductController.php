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
 *     path="/api/admin/products",
 *     summary="Lấy danh sách sản phẩm",
 *     tags={"Product"},
 *     security={{"Bearer": {}}},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         required=false,
 *         @OA\Schema(type="integer"),
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Danh sách sản phẩm",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array",
 *                 @OA\Items(ref="#/components/schemas/Product")
 *             ),
 *             @OA\Property(property="message", type="string", example="Lấy danh sách sản phẩm thành công."),
 *         )
 *     )
 * )
 */

    public function index()
    {
        $products = Product::with(['category', 'brand', 'tags', 'productImages', 'productVariants.productColor', 'productVariants.productSize'])->get();
        return response()->json($products);
    }
 /**
 * @OA\Post(
 *     path="/api/admin/products",
 *     summary="Thêm sản phẩm mới",
 *     tags={"Product"},
 *     security={{"Bearer": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(property="name", type="string", description="Tên sản phẩm", example="Sản phẩm 1"),
 *                 @OA\Property(property="slug", type="string", description="Đường dẫn sản phẩm", example="san-pham-1"),
 *                 @OA\Property(property="sku", type="string", description="Mã SKU", example="SKU123"),
 *                 @OA\Property(property="img_thumbnail", type="string", description="Hình ảnh đại diện", example="thumbnail.png"),
 *                 @OA\Property(property="price_regular", type="number", format="float", description="Giá chính", example=99.99),
 *                 @OA\Property(property="price_sale", type="number", format="float", description="Giá giảm", example=79.99),
 *                 @OA\Property(property="description", type="string", description="Mô tả ngắn", example="Mô tả sản phẩm 1"),
 *                 @OA\Property(property="content", type="string", description="Nội dung chi tiết", example="Nội dung sản phẩm 1"),
 *                 @OA\Property(property="user_manual", type="string", description="Chất liệu", example="Chất liệu A"),
 *                 @OA\Property(property="view", type="integer", description="Số lượt xem", example=100),
 *                 @OA\Property(property="is_active", type="boolean", description="Trạng thái kích hoạt", example=true),
 *                 @OA\Property(property="is_new", type="boolean", description="Sản phẩm mới", example=true),
 *                 @OA\Property(property="is_show_home", type="boolean", description="Hiển thị trên trang chính", example=true),
 *                 @OA\Property(property="category_id", type="integer", description="ID danh mục", example=1),
 *                 @OA\Property(property="brand_id", type="integer", description="ID thương hiệu", example=1),
 *             ),
 *         ),
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Thêm sản phẩm thành công",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Thêm sản phẩm thành công."),
 *             @OA\Property(property="data", type="object", ref="#/components/schemas/Product")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Yêu cầu không hợp lệ",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Dữ liệu không hợp lệ."),
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
            $dataProduct['img_thumbnail'] = Storage::put('products', $request->file('img_thumbnail'));
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
                    $dataProductVariant['image'] = Storage::put('products', $variant['image']);
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
 *     path="/api/admin/products/{id}",
 *     summary="Lấy chi tiết sản phẩm",
 *     tags={"Product"},
 *     security={{"Bearer": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer"),
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Thông tin sản phẩm",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Lấy thành công dữ liệu của bản ghi 1"),
 *             @OA\Property(property="data", type="object", ref="#/components/schemas/Product")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Sản phẩm không tồn tại",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Không tìm thấy sản phẩm"),
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
 *     path="/api/admin/products/{id}",
 *     summary="Cập nhật sản phẩm",
 *     tags={"Product"},
 *     security={{"Bearer": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer"),
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(property="name", type="string", description="Tên sản phẩm", example="Sản phẩm 1"),
 *                 @OA\Property(property="slug", type="string", description="Đường dẫn sản phẩm", example="san-pham-1"),
 *                 @OA\Property(property="sku", type="string", description="Mã SKU", example="SKU123"),
 *                 @OA\Property(property="img_thumbnail", type="string", description="Hình ảnh đại diện", example="thumbnail.png"),
 *                 @OA\Property(property="price_regular", type="number", format="float", description="Giá chính", example=99.99),
 *                 @OA\Property(property="price_sale", type="number", format="float", description="Giá giảm", example=79.99),
 *                 @OA\Property(property="description", type="string", description="Mô tả ngắn", example="Mô tả sản phẩm 1"),
 *                 @OA\Property(property="content", type="string", description="Nội dung chi tiết", example="Nội dung sản phẩm 1"),
 *                 @OA\Property(property="user_manual", type="string", description="Chất liệu", example="Chất liệu A"),
 *                 @OA\Property(property="view", type="integer", description="Số lượt xem", example=100),
 *                 @OA\Property(property="is_active", type="boolean", description="Trạng thái kích hoạt", example=true),
 *                 @OA\Property(property="is_new", type="boolean", description="Sản phẩm mới", example=true),
 *                 @OA\Property(property="is_show_home", type="boolean", description="Hiển thị trên trang chính", example=true),
 *                 @OA\Property(property="category_id", type="integer", description="ID danh mục", example=1),
 *                 @OA\Property(property="brand_id", type="integer", description="ID thương hiệu", example=1),
 *             ),
 *         ),
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Cập nhật sản phẩm thành công",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Cập nhật sản phẩm thành công."),
 *             @OA\Property(property="data", type="object", ref="#/components/schemas/Product")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Sản phẩm không tồn tại",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Không tìm thấy sản phẩm"),
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Yêu cầu không hợp lệ",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Dữ liệu không hợp lệ."),
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
            Storage::delete($product->img_thumbnail);
            $dataProduct['img_thumbnail'] = Storage::put('products', $request->file('img_thumbnail'));
        }

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
                Storage::delete($variant->image);
                $dataProductVariant['image'] = Storage::put('products', $value['image']);
            } else {
                $dataProductVariant['image'] = $variant->image; // Giữ nguyên hình ảnh cũ
            }
            $variant->update($dataProductVariant);
            unset($existingVariants[$variantKey]);
        } else {
            // Thêm biến thể mới
            $dataProductVariant['product_id'] = $product->id;
            if (isset($value['image']) && $value['image'] instanceof \Illuminate\Http\UploadedFile) {
                $dataProductVariant['image'] = Storage::put('products', $value['image']);
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
 *         @OA\Schema(type="integer"),
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Xóa sản phẩm thành công",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Xóa sản phẩm thành công."),
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Sản phẩm không tồn tại",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Không tìm thấy sản phẩm"),
 *         )
 *     )
 * )
 */


 public function destroy(Product $product)
 {
     DB::beginTransaction();
     try {
         // Xóa hình ảnh thumbnail
         Storage::delete($product->img_thumbnail);
 
         // Xóa hình ảnh sản phẩm
         if ($product->productImages && $product->productImages->isNotEmpty()) {
             foreach ($product->productImages as $gallery) {
                 Storage::delete($gallery->image);
                 $gallery->delete();
             }
         }
 
         // Xóa biến thể và hình ảnh của nó
         if ($product->variants && $product->variants->isNotEmpty()) {
             foreach ($product->variants as $variant) {
                 Storage::delete($variant->image);
                 $variant->delete();
             }
         }
 
         // Ngắt kết nối thẻ
         $product->tags()->detach();
 
         $product->delete();
         DB::commit();
         return response()->json(['message' => 'Xoá sản phẩm thành công'], 200);
     } catch (\Exception $e) {
         DB::rollBack();
         Log::error($e->getMessage());
         return response()->json(['error' => 'Lỗi xoá sản phẩm : ' . $e->getMessage()], 500);
     }
 }
 
}
