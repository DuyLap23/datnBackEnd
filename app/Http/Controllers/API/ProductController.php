<?php

namespace App\Http\Controllers\API;

use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'brand'])->get();
        return response()->json($products);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'nullable|unique:products,slug',
                'sku' => 'nullable|unique:products,sku',
                'img_thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                'price_regular' => 'required|numeric|min:0',
                'price_sale' => 'nullable|numeric|min:0',
                'description' => 'required|string',
                'content' => 'required|string',
                'user_manual' => 'required|string',
                'view' => 'required|integer|min:0',
                'is_active' => 'required|boolean',
                'is_new' => 'required|boolean',
                'is_show_home' => 'required|boolean',
                'category_id' => 'required|exists:categories,id',
                'brand_id' => 'required|exists:brands,id',
            ]);
    
            $product = new Product($data);
    
            if ($request->hasFile('img_thumbnail')) {
                $thumbnail = $request->file('img_thumbnail');
                $thumbnailName = time() . '_' . Str::random(10) . '.' . $thumbnail->getClientOriginalExtension();
                $product->img_thumbnail = $thumbnail->storeAs('products', $thumbnailName, 'public');
            }
    
            $product->save();
            DB::commit();
    
            return response()->json($product->load(['category', 'brand']), 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => 'Lỗi tạo sản phẩm thất bại: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $product = Product::findOrFail($id);
            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'slug' => 'sometimes|required|string|unique:products,slug,' . $id,
                'sku' => 'sometimes|required|string|unique:products,sku,' . $id,
                'img_thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'price_regular' => 'sometimes|required|numeric|min:0',
                'price_sale' => 'nullable|numeric|min:0',
                'description' => 'sometimes|required|string',
                'content' => 'sometimes|required|string',
                'user_manual' => 'sometimes|required|string',
                'view' => 'sometimes|required|integer|min:0',
                'is_active' => 'sometimes|required|boolean',
                'is_new' => 'sometimes|required|boolean',
                'is_show_home' => 'sometimes|required|boolean',
                'category_id' => 'sometimes|required|exists:categories,id',
                'brand_id' => 'sometimes|required|exists:brands,id',
            ]);

            if ($request->hasFile('img_thumbnail')) {
                if ($product->img_thumbnail) {
                    Storage::delete(str_replace('/storage', 'public', $product->img_thumbnail));
                }
                $thumbnail = $request->file('img_thumbnail');
                $thumbnailName = time() . '_' . Str::random(10) . '.' . $thumbnail->getClientOriginalExtension();
                $path = $thumbnail->storeAs('public/products', $thumbnailName);
                $product->img_thumbnail = Storage::url($path);
            }

            $product->update($request->except(['img_thumbnail']));
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật sản phẩm thành công.',
                'data' => $product->load(['category', 'brand']),
            ], 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật sản phẩm thất bại',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $product = Product::findOrFail($id);
            if ($product->img_thumbnail) {
                Storage::delete(str_replace('/storage', 'public', $product->img_thumbnail));
            }
            $product->delete();
            DB::commit();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => 'Lỗi xoá sản phẩm thất bại'], 500);
        }
    }
}
