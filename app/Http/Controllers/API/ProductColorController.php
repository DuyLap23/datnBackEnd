<?php

namespace App\Http\Controllers\API;



use App\Models\ProductColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class ProductColorController extends Controller
{

    public function index()
    {
        $colors = ProductColor::all();
        return response()->json($colors);
    }

   
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                   'name' => 'required|string|max:255'
            ]);

            $size = ProductColor::create($validatedData);
            return response()->json($size, 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error creating size', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $color = ProductColor::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $color
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy màu sắc.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching color: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy thông tin màu sắc.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $color = ProductColor::findOrFail($id);

            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:product_colors,name,' . $id
            ]);

            $color->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Màu sắc đã được cập nhật thành công.',
                'data' => $color
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy màu sắc.',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating color: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể cập nhật màu sắc.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $color = ProductColor::findOrFail($id);
            $color->delete();

            return response()->json([
                'success' => true,
                'message' => 'Màu sắc đã được xóa thành công.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy màu sắc.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting color: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa màu sắc.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}


