<?php

namespace App\Http\Controllers\API;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use App\Models\ProductImage;
use App\Models\Tag;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'brand', 'tags', 'productImages', 'productVariants.productColor', 'productVariants.productSize'])->get();
        return response()->json($products);
    }

    public function store(ProductRequest $request)
    {
        DB::beginTransaction();
        try {
            $dataProduct = $request->except(['product_variants', 'tags', 'product_images']);
            $dataProduct['is_active'] = $request->input('is_active', 0);
            $dataProduct['is_new'] = $request->input('is_new', 0);
            $dataProduct['is_show_home'] = $request->input('is_show_home', 0);
            $dataProduct['slug'] = Str::slug($dataProduct['name']) . '-' . Str::uuid();
            $dataProduct['sku'] = Str::uuid();

            if ($request->hasFile('img_thumbnail')) {
                $dataProduct['img_thumbnail'] = Storage::put('products', $request->file('img_thumbnail'));
            }

            $product = Product::create($dataProduct);

            // Xử lý biến thể
            foreach ($request->product_variants as $key => $value) {
                $tmp = explode('-', $key);
                $dataProductVariant = [
                    'product_id' => $product->id,
                    'product_size_id' => $tmp[0],
                    'product_color_id' => $tmp[1],
                    'quantity' => $value['quantity'] ?? 0,
                    'image' => $value['image'] ?? null,
                ];
                if (isset($dataProductVariant['image']) && $dataProductVariant['image'] instanceof \Illuminate\Http\UploadedFile) {
                    $dataProductVariant['image'] = Storage::put('products', $dataProductVariant['image']);
                }
                ProductVariant::create($dataProductVariant);
            }

            // Xử lý thẻ
            if ($request->has('tags')) {
                $product->tags()->sync($request->tags);
            }

            // Xử lý hình ảnh sản phẩm
            if ($request->hasFile('product_images')) {
                foreach ($request->file('product_images') as $image) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image' => Storage::put('products', $image),
                    ]);
                }
            }

            DB::commit();

            return response()->json($product->load(['category', 'brand', 'tags', 'productImage', 'variants.productColor', 'variants.productSize']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => 'Lỗi thêm sản phẩm: ' . $e->getMessage()], 500);
        }
    }
    public function show(Product $product)
    {
        return response()->json($product->load(['category', 'brand', 'tags', 'productImage', 'variants.productColor', 'variants.productSize']));
    }

    public function update(Request $request, Product $product)
    {
        DB::beginTransaction();
        try {
            $dataProduct = $request->except(['product_variants', 'tags', 'product_images']);
            $dataProduct['is_active'] = $request->input('is_active', 0);
            $dataProduct['is_hot_deal'] = $request->input('is_hot_deal', 0);
            $dataProduct['is_good_deal'] = $request->input('is_good_deal', 0);
            $dataProduct['is_new'] = $request->input('is_new', 0);
            $dataProduct['is_show_home'] = $request->input('is_show_home', 0);
            $dataProduct['slug'] = Str::slug($dataProduct['name']) . '-' . $dataProduct['sku'];

            if ($request->hasFile('img_thumbnail')) {
                Storage::delete($product->img_thumbnail);
                $dataProduct['img_thumbnail'] = Storage::put('products', $request->file('img_thumbnail'));
            }

            $product->update($dataProduct);

            // Xử lý biến thể
            $existingVariants = $product->variants()->get()->keyBy(function ($item) {
                return $item->product_size_id . '-' . $item->product_color_id;
            });

            foreach ($request->product_variants as $key => $value) {
                $tmp = explode('-', $key);
                $dataProductVariant = [
                    'product_size_id' => $tmp[0],
                    'product_color_id' => $tmp[1],
                    'quantity' => $value['quantity'] ?? 0,
                    'image' => $value['image'] ?? null,
                ];
                $variantKey = $dataProductVariant['product_size_id'] . '-' . $dataProductVariant['product_color_id'];

                if (isset($existingVariants[$variantKey])) {
                    $variant = $existingVariants[$variantKey];
                    if (isset($dataProductVariant['image']) && $dataProductVariant['image'] instanceof \Illuminate\Http\UploadedFile) {
                        Storage::delete($variant->image);
                        $dataProductVariant['image'] = Storage::put('products', $dataProductVariant['image']);
                    } else {
                        $dataProductVariant['image'] = $variant->image;
                    }
                    $variant->update($dataProductVariant);
                    unset($existingVariants[$variantKey]);
                } else {
                    $dataProductVariant['product_id'] = $product->id;
                    if (isset($dataProductVariant['image']) && $dataProductVariant['image'] instanceof \Illuminate\Http\UploadedFile) {
                        $dataProductVariant['image'] = Storage::put('products', $dataProductVariant['image']);
                    }
                    ProductVariant::create($dataProductVariant);
                }
            }

            foreach ($existingVariants as $variant) {
                Storage::delete($variant->image);
                $variant->delete();
            }

            // Xử lý thẻ
            if ($request->has('tags')) {
                $product->tags()->sync($request->tags);
            }

            // Xử lý hình ảnh sản phẩm
            if ($request->hasFile('product_images')) {
                foreach ($request->file('product_images') as $image) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image' => Storage::put('products', $image),
                    ]);
                }
            }

            DB::commit();

            return response()->json($product->load(['category', 'tags', 'productImage', 'variants.productColor', 'variants.productSize']));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => 'Lỗi chỉnh sửa sản phẩm: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Product $product)
    {
        DB::beginTransaction();
        try {
            // Delete thumbnail
            Storage::delete($product->img_thumbnail);

            // Delete product galleries
            foreach ($product->productGalleries as $gallery) {
                Storage::delete($gallery->image);
                $gallery->delete();
            }

            // Delete variant images and variants
            foreach ($product->variants as $variant) {
                Storage::delete($variant->image);
                $variant->delete();
            }

            // Detach tags
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
