<?php

namespace App\Http\Controllers\API;

use App\Models\ProductSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductSizeController extends Controller
{
    public function index()
    {
        $sizes = ProductSize::all();
        return response()->json($sizes);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:1,2'
            ]);

            $size = ProductSize::create($validatedData);
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
            $color = ProductSize::findOrFail($id);
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
            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'type' => 'sometimes|required|in:1,2'
            ]);

            $size = ProductSize::findOrFail($id);
            $size->update($validatedData);
            return response()->json($size);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Size not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error updating size', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $size = ProductSize::findOrFail($id);
            $size->delete();
            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Size not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error deleting size', 'error' => $e->getMessage()], 500);
        }
    }
}