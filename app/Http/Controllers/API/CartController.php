<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart; 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
 *             @OA\Property(property="quantity", type="integer", description="Số lượng sản phẩm cần thêm. Phải lớn hơn 0."),
 *             @OA\Property(property="color", type="string", description="Màu sắc của sản phẩm."),
 *             @OA\Property(property="size", type="string", description="Kích thước của sản phẩm.")
 *         )
 *     ),
 *     @OA\Response(response="201", description="Thêm sản phẩm thành công"),
 *     @OA\Response(response="400", description="Thông tin không hợp lệ", 
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Số lượng sản phẩm vượt quá giới hạn cho phép.")
 *         )
 *     ),
 *     @OA\Response(response="404", description="Sản phẩm không tồn tại", 
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Sản phẩm không tồn tại.")
 *         )
 *     ),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="500", description="Có lỗi xảy ra khi thêm sản phẩm", 
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Có lỗi xảy ra khi thêm sản phẩm."),
 *             @OA\Property(property="details", type="string", example="Chi tiết lỗi.")
 *         )
 *     )
 * )
 */
public function addProductToCart(Request $request)
{
   
    if (!Auth::check()) {
        return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
    }
    // Kiểm tra và xác thực dữ liệu
    $validatedData = $request->validate([
        'product_id' => 'required|integer|exists:products,id', 
        'quantity' => 'required|integer|min:1|max:100', 
        'color' => 'nullable|string',
        'size' => 'nullable|string',
    ]);

        // Kiểm tra tồn tại sản phẩm
        $product = Product::find($validatedData['product_id']);
        // Kiểm tra màu sắc từ bảng product_colors
        $colorId = ProductColor::where('name', $validatedData['color'])->value('id');

        // Kiểm tra kích thước từ bảng product_sizes
        $sizeId = ProductSize::where('name', $validatedData['size'])->value('id');
    if (!$product) {
        return response()->json(['error' => 'Sản phẩm không tồn tại.'], 404);
    }
      // Kiểm tra số lượng từ bảng product_variants
      $productVariant = ProductVariant::where('product_id', $validatedData['product_id'])
      ->where('product_color_id', $colorId)
      ->where('product_size_id', $sizeId)
      ->first();

        if (!$productVariant) {
            return response()->json(['error' => 'Biến thể sản phẩm không tồn tại.'], 404);
        }

        // Kiểm tra số lượng có đủ không
        if ($productVariant->quantity < $validatedData['quantity']) {
            return response()->json(['error' => 'Số lượng sản phẩm không đủ.'], 400);
        }
    try {
        // Kiểm tra sản phẩm có trong giỏ hàng không
        $cartItem = Cart::where('user_id', $request->user()->id)
            ->where('product_id', $validatedData['product_id'])
            ->where('color', $validatedData['color']) 
            ->where('size', $validatedData['size']) 
            ->first();

        if ($cartItem) {
            // Cập nhật số lượng nếu sản phẩm đã có trong giỏ hàng
            $newQuantity = $cartItem->quantity + $validatedData['quantity'];
            
            // Kiểm tra giới hạn số lượng
            if ($newQuantity > 100) { 
                return response()->json(['error' => 'Số lượng sản phẩm vượt quá giới hạn cho phép.'], 400);
            }

            $cartItem->quantity = $newQuantity;
            $cartItem->save();

            return response()->json([
                'message' => 'Cập nhật số lượng sản phẩm thành công.',
                'cart_item' => $cartItem,
            ], 200);
        } else {
            // Thêm sản phẩm mới vào giỏ hàng
            $price = $product->price_sale > 0 ? $product->price_sale : $product->price_regular;

            $cartItem = Cart::create(array_merge($validatedData, [
                'user_id' => $request->user()->id,
                'price' => $price, 
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
 * @OA\Delete(
 *     path="/api/carts/{id}",
 *     tags={"Cart"},
 *     summary="Xóa sản phẩm khỏi giỏ hàng",
 *     security={{"Bearer": {}}},
 *     description="Xóa sản phẩm khỏi giỏ hàng của người dùng theo ID sản phẩm.",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer", description="ID của sản phẩm trong giỏ hàng cần xóa.")
 *     ),
 *     @OA\Response(response="204", description="Xóa sản phẩm thành công"),
 *     @OA\Response(response="404", description="Sản phẩm không tìm thấy"),
 *     @OA\Response(response="401", description="Unauthorized")
 * )
 */
public function deleteProductFromCart($id)
{
    if (!Auth::check()) {
        return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
    }
    
    
    // Lấy ID người dùng hiện tại
    $userId = auth('api')->user()->id;

    // Tìm sản phẩm trong giỏ hàng của người dùng
    $cart = Cart::where('id', $id)->where('user_id', $userId)->first();
    
    // Kiểm tra xem sản phẩm có tồn tại hay không
    if (!$cart) {
        return response()->json(['error' => 'Sản phẩm không tìm thấy'], 404);
    }

    // Xóa sản phẩm
    $cart->delete();
    return response()->json(['message' => 'Xóa sản phẩm thành công.'], 200);
}

  /**
 * @OA\Get(
 *     path="/api/carts",
 *     tags={"Cart"},
 *     summary="Lấy danh sách sản phẩm trong giỏ hàng",
 *     security={{"Bearer": {}}},
 *     description="Lấy danh sách tất cả sản phẩm trong giỏ hàng của người dùng.",
 *     @OA\Response(response="200", description="Danh sách sản phẩm trong giỏ hàng", 
 *         @OA\JsonContent(
 *             @OA\Property(property="cart_items", type="array", 
 *                 @OA\Items(ref="#/components/schemas/Cart")),
 *             @OA\Property(property="total_price", type="number", format="float", description="Tổng giá trị của sản phẩm trong giỏ hàng")
 *         )
 *     ),
 *     @OA\Response(response="401", description="Unauthorized")
 *     
 * )
 */
public function listProductsInCart(Request $request)
{
    if (!Auth::check()) {
        return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
    }

    // Lấy danh sách sản phẩm trong giỏ hàng của người dùng cùng với thông tin sản phẩm
    $cartItems = Cart::where('user_id', $request->user()->id)
        ->with(['product' => function ($query) {
            $query->select('id', 'name', 'slug', 'sku', 'img_thumbnail', 'price_regular', 'price_sale', 'description');
        }])
        ->get();

    // Tính tổng tiền của sản phẩm trong giỏ hàng
    $totalPrice = $cartItems->sum(function ($cartItem) {
        $price = $cartItem->product->price_sale > 0 ? $cartItem->product->price_sale : $cartItem->product->price_regular;
        return $price * $cartItem->quantity; 
    });

    // Trả về danh sách sản phẩm trong giỏ hàng và tổng tiền
    return response()->json([
        'cart_items' => $cartItems->map(function ($cartItem) {
            $product = $cartItem->product;
            return [
                'id' => $cartItem->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'img_thumbnail' => $product->img_thumbnail,
                'quantity' => $cartItem->quantity,
                'color' => $cartItem->color, 
                'size' => $cartItem->size, 
                'price_regular' => $product->price_regular, 
                'price_sale' => $product->price_sale, 
                'price' => $product->price_sale > 0 ? $product->price_sale : $product->price_regular,
                'total' => ($product->price_sale > 0 ? $product->price_sale : $product->price_regular) * $cartItem->quantity,
                'description' => $product->description,
            ];
        }),
        'total_price' => $totalPrice,
    ], 200);
}


    

}
