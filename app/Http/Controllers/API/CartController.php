<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart; 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


/**
 * @OA\Schema(
 *     schema="Cart",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=123),
 *     @OA\Property(property="product_id", type="integer", example=456),
 *     @OA\Property(property="quantity", type="integer", example=2),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

class CartController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/carts",
     *     tags={"Cart"},
     *     summary="Hiển thị danh sách giỏ hàng của người dùng",
     *     security={{"Bearer": {}}},
     *     description="Lấy danh sách tất cả các sản phẩm trong giỏ hàng của người dùng hiện tại.",
     *     @OA\Response(response="200", description="Danh sách giỏ hàng thành công"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function index(Request $request)
    {
        $carts = Cart::with('product')
            ->where('user_id', $request->user()->id)
            ->get();
        return response()->json($carts);
    }

    /**
     * @OA\Post(
     *     path="/api/carts",
     *     tags={"Cart"},
     *     summary="Thêm sản phẩm vào giỏ hàng",
     *     security={{"Bearer": {}}},
     *     description="Thêm một sản phẩm vào giỏ hàng của người dùng. Nếu sản phẩm đã tồn tại, số lượng sẽ được cập nhật.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id", "quantity"},
     *             @OA\Property(property="product_id", type="integer", description="ID của sản phẩm cần thêm vào giỏ hàng."),
     *             @OA\Property(property="quantity", type="integer", description="Số lượng sản phẩm cần thêm. Phải lớn hơn 0.")
     *         )
     *     ),
     *     @OA\Response(response="201", description="Thêm sản phẩm thành công"),
     *     @OA\Response(response="400", description="Thông tin không hợp lệ"),
     *     @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function addProductToCart(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|integer|exists:products,id', 
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $cartItem = Cart::where('user_id', $request->user()->id)
                ->where('product_id', $validatedData['product_id'])
                ->first();

            if ($cartItem) {
                $cartItem->quantity += $validatedData['quantity'];
                $cartItem->save();
                return response()->json([
                    'message' => 'Cập nhật số lượng sản phẩm thành công.',
                    'cart_item' => $cartItem,
                ], 200);
            } else {
                $cartItem = Cart::create(array_merge($validatedData, [
                    'user_id' => $request->user()->id,
                ]));
                return response()->json([
                    'message' => 'Thêm sản phẩm vào giỏ hàng thành công.',
                    'cart_item' => $cartItem,
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Có lỗi xảy ra khi thêm sản phẩm.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/carts/{id}",
     *     tags={"Cart"},
     *     summary="Cập nhật số lượng sản phẩm trong giỏ hàng",
     *     description="Cập nhật số lượng của sản phẩm đã có trong giỏ hàng. Nếu số lượng không hợp lệ, trả về lỗi.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", description="ID của sản phẩm trong giỏ hàng cần cập nhật.")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="integer", description="Số lượng sản phẩm cần cập nhật. Phải lớn hơn 0.")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Cập nhật sản phẩm thành công"),
     *     @OA\Response(response="404", description="Sản phẩm không tìm thấy"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     security={{"Bearer": {}}}
     * )
     */
    public function update(Request $request, $id)
    {
        $cart = Cart::find($id);
        if (!$cart) {
            return response()->json(['error' => 'Sản phẩm không tìm thấy'], 404);
        }

        $validatedData = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart->update($validatedData);
        return response()->json([
            'message' => 'Cập nhật số lượng sản phẩm thành công.',
            'cart_item' => $cart,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/carts/{id}",
     *     tags={"Cart"},
     *     summary="Xóa sản phẩm khỏi giỏ hàng",
     *     description="Xóa sản phẩm khỏi giỏ hàng của người dùng theo ID sản phẩm.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", description="ID của sản phẩm trong giỏ hàng cần xóa.")
     *     ),
     *     @OA\Response(response="204", description="Xóa sản phẩm thành công"),
     *     @OA\Response(response="404", description="Sản phẩm không tìm thấy"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     security={{"Bearer": {}}}
     * )
     */
    public function destroy($id)
    {
        $cart = Cart::find($id);
        if (!$cart) {
            return response()->json(['error' => 'Sản phẩm không tìm thấy'], 404);
        }

        $cart->delete();
        return response()->json(['message' => 'Xóa sản phẩm thành công.'], 204);
    }
}
