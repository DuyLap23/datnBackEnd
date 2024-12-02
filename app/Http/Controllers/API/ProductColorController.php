<?php

namespace App\Http\Controllers\API;



use App\Models\ProductColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class ProductColorController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/product-colors",
     *     summary="Lấy danh sách màu sắc sản phẩm",
     *     tags={"Product Colors"},
     *     @OA\Response(
     *         response=200,
     *         description="Thành công",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Đỏ")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $colors = ProductColor::all();
        return response()->json(
            [
                'success' => true,
                'message' => 'Lấy thành công size',
                'colors' =>  $colors,
            ],
            200,
        );

    }
 /**
     * @OA\Post(
     *     path="/api/product-colors",
     *     summary="Thêm màu sắc mới",
     *     tags={"Product Colors"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Xanh lá")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tạo thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=2),
     *             @OA\Property(property="name", type="string", example="Xanh lá")
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
/**
     * @OA\Get(
     *     path="/api/product-colors/{id}",
     *     summary="Lấy chi tiết màu sắc",
     *     tags={"Product Colors"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của màu sắc",
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
     *                 @OA\Property(property="name", type="string", example="Đỏ")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy màu sắc.")
     *         )
     *     )
     * )
     */
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
 /**
     * @OA\Put(
     *     path="/api/product-colors/{id}",
     *     summary="Cập nhật màu sắc",
     *     tags={"Product Colors"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của màu sắc",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Xanh dương")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Màu sắc đã được cập nhật thành công."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Xanh dương")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy màu sắc.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Dữ liệu không hợp lệ."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
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
      /**
     * @OA\Delete(
     *     path="/api/product-colors/{id}",
     *     summary="Xóa màu sắc",
     *     tags={"Product Colors"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của màu sắc",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Xóa thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Màu sắc đã được xóa thành công.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tìm thấy màu sắc.")
     *         )
     *     )
     * )
     */

    public function destroy($id)
    {
        try {
            $color = ProductColor::findOrFail($id);
            $cartItems = Cart::whereHas('products', function ($query) use ($color) {
                $query->where('color_id', $color->id); 
            })->get();
            foreach ($cartItems as $cartItem) {
                $cartItem->products->each(function ($product) use ($color) {
                    $product->update(['status' => 1]); 
                });
            }
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


