<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cart; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/create-store",
     * summary="Tạo mới cửa hàng",
     * description="Tạo mới cửa hàng cho giỏ hàng",
     * tags={"Create Cart"},
     * @OA\RequestBody(
     * required=true,
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(
     * @OA\Property(
     * property="name",
     * type="string",
     * description="Tên cửa hàng",
     * example="Cửa hàng 1",
     * ),
     * ),
     * ),
     * ),
     * @OA\Response(
     * response=200,
     * description="Thành công",
     * @OA\JsonContent(
     * @OA\Property(
     * property="success",
     * type="boolean",
     * example=true,
     * ),
     * @OA\Property(
     * property="message",
     * type="string",
     * example="Success",
     * ),
     * @OA\Property(
     * property="status",
     * type="string",
     * example="200",
     * ),
     * @OA\Property(
     * property="data",
     * type="object",
     * @OA\Property(
     * property="id",
     * type="integer",
     * example=1,
     * ),
     * @OA\Property(
     * property="name",
     * type="string",
     * example="Cửa hàng 1",
     * ),
     * ),
     * ),
     * ),
     * )
     */
    public function store(Request $request)
    {
        // Xác thực dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'status' => '400',
            ], 400);
        }

        // Tạo mới giỏ hàng
        $cart = Cart::create($request->only('name'));

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'status' => '200',
            'data' => [
                'id' => $cart->id,
                'name' => $cart->name,
            ],
        ], 200);
    }

    /**
     * @OA\Get(
     * path="/api/cart/{id}",
     * summary="Lấy thông tin cửa hàng",
     * description="Hiển thị thông tin cửa hàng theo ID",
     * tags={"Cart"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * @OA\Schema(type="integer"),
     * ),
     * @OA\Response(
     * response=200,
     * description="Thành công",
     * @OA\JsonContent(
     * @OA\Property(
     * property="success",
     * type="boolean",
     * example=true,
     * ),
     * @OA\Property(
     * property="data",
     * type="object",
     * @OA\Property(
     * property="id",
     * type="integer",
     * example=1,
     * ),
     * @OA\Property(
     * property="name",
     * type="string",
     * example="Cửa hàng 1",
     * ),
     * ),
     * ),
     * ),
     * @OA\Response(
     * response=404,
     * description="Không tìm thấy",
     * )
     * )
     */
    public function show(string $id)
    {
        $cart = Cart::find($id);

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found',
                'status' => '404',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cart->id,
                'name' => $cart->name,
            ],
            'status' => '200',
        ]);
    }

    /**
     * @OA\Put(
     * path="/api/cart/{id}",
     * summary="Cập nhật cửa hàng",
     * description="Cập nhật thông tin cửa hàng theo ID",
     * tags={"Cart"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * @OA\Schema(type="integer"),
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(
     * @OA\Property(
     * property="name",
     * type="string",
     * description="Tên cửa hàng",
     * example="Cửa hàng 2",
     * ),
     * ),
     * ),
     * ),
     * @OA\Response(
     * response=200,
     * description="Cập nhật thành công",
     * ),
     * @OA\Response(
     * response=404,
     * description="Không tìm thấy",
     * )
     * )
     */
    public function update(Request $request, string $id)
    {
        $cart = Cart::find($id);

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found',
                'status' => '404',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'status' => '400',
            ], 400);
        }

        $cart->update($request->only('name'));

        return response()->json([
            'success' => true,
            'message' => 'Updated successfully',
            'status' => '200',
            'data' => [
                'id' => $cart->id,
                'name' => $cart->name,
            ],
        ]);
    }

    /**
     * @OA\Delete(
     * path="/api/cart/{id}",
     * summary="Xóa cửa hàng",
     * description="Xóa cửa hàng theo ID",
     * tags={"Cart"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * @OA\Schema(type="integer"),
     * ),
     * @OA\Response(
     * response=204,
     * description="Xóa thành công",
     * ),
     * @OA\Response(
     * response=404,
     * description="Không tìm thấy",
     * )
     * )
     */
    public function destroy(string $id)
    {
        $cart = Cart::find($id);

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found',
                'status' => '404',
            ], 404);
        }

        $cart->delete();

        return response()->json(null, 204);
    }
}
