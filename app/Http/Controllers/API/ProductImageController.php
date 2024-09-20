<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Str;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductImageController extends Controller
{
    const PATH_UPLOAD = 'products';
    public function index($productId)
    {
        $images = ProductImage::where('product_id', $productId)->get();
        return response()->json($images);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validate([
                'image' => ['required', 'array'],
                'image.*' => ['image', 'mimes:jpeg,jpg,png,gif,svg,webp', 'max:2048'],
                'product_id' => ['required', 'exists:products,id'],
            ]);

            $uploadedImages = [];

            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $image) {
                    $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                    $path = $image->storeAs(self::PATH_UPLOAD, $imageName, 'public');

                    $imageModel = new ProductImage();
                    $imageModel->product_id = $data['product_id'];
                    $imageModel->image = Storage::url($path);
                    $imageModel->save();

                    $uploadedImages[] = $imageModel;
                }
            }

            DB::commit();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Thêm ảnh sản phẩm thành công.',
                    'images' => $uploadedImages
                ],
                201
            );
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Thêm ảnh sản phẩm thất bại',
                    'error' => $exception->getMessage()
                ],
                500
            );
        }
    }
    public function show($id)
    {
        try {
            $color = ProductImage::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $color
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy ảnh.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching color: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy thông tin ảnh.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function destroy($id)
    {
        $image = ProductImage::findOrFail($id);
        
        // Delete the record from database first
        $image->delete();
        
        // Then delete the file from storage
        Storage::delete(str_replace('/storage', 'public', $image->image));
        
        return response()->json(['message' => 'Image deleted successfully']);
    }
}