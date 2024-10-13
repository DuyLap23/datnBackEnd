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
    
 /**
     * @OA\Get(
     *     path="/api/product-sizes",
     *     summary="Lấy danh sách kích thước sản phẩm",
     *     tags={"Product Sizes"},
     *     @OA\Response(
     *         response=200,
     *         description="Thành công",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="S"),
     *                 @OA\Property(property="type", type="integer", example=1)
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $sizes = ProductSize::all();
       
        return response()->json(
            [
                'success' => true,
                'message' => 'Lấy thành công size',
                'sizes' =>  $sizes,
            ],
            200,
        );
    }
  /**
     * @OA\Post(
     *     path="/api/product-sizes",
     *     summary="Thêm kích thước mới",
     *     tags={"Product Sizes"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="M"),
     *             @OA\Property(property="type", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tạo thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=2),
     *             @OA\Property(property="name", type="string", example="M"),
     *             @OA\Property(property="type", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
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
    
    /**
     * @OA\Get(
     *     path="/api/product-sizes/{id}",
     *     summary="Lấy chi tiết kích thước",
     *     tags={"Product Sizes"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của kích thước",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="S"),
     *                 @OA\Property(property="type", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy kích thước.")
     *         )
     *     )
     * )
     */
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
    
    /**
     * @OA\Put(
     *     path="/api/product-sizes/{id}",
     *     summary="Cập nhật kích thước",
     *     tags={"Product Sizes"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của kích thước",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="L"),
     *             @OA\Property(property="type", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="L"),
     *             @OA\Property(property="type", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Size not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
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
 /**
     * @OA\Delete(
     *     path="/api/product-sizes/{id}",
     *     summary="Xóa kích thước",
     *     tags={"Product Sizes"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của kích thước",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Xóa thành công"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Size not found")
     *         )
     *     )
     * )
     */
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