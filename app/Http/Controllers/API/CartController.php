<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;

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
        try {
            $validatedData = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'quantity' => 'required|integer|min:1|max:100',
                'color' => 'required|string',
                'size' => 'required|string',
            ], [
                'product_id.required' => 'Bạn chưa chọn sản phẩm.',
                'quantity.required' => 'Bạn chưa chọn số lượng.',
                'color.required' => 'Bạn chưa chọn màu sắc.',
                'size.required' => 'Bạn chưa chọn kích thước.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422, [], JSON_UNESCAPED_UNICODE);
        }


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

        // Kiểm tra xem biến thể sản phẩm có tồn tại không
        if (!$productVariant) {
            // Kiểm tra màu sắc
            $colorExists = ProductColor::find($colorId);
            if (!$colorExists) {
                return response()->json(['error' => 'Màu sắc không tồn tại.'], 404);
            }

            // Kiểm tra kích thước
            $sizeExists = ProductSize::find($sizeId);
            if (!$sizeExists) {
                return response()->json(['error' => 'Size không tồn tại.'], 404);
            }

            // Nếu cả màu sắc và kích thước đều tồn tại nhưng không tìm thấy biến thể
            return response()->json(['error' => 'Không tìm thấy sản phẩm có màu sắc và sze đã chọn.'], 404);
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
                    'product_variant_id' => $productVariant->id,
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
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
        }

        // Lấy danh sách sản phẩm trong giỏ hàng của người dùng cùng với thông tin sản phẩm và biến thể
        $cartItems = Cart::where('user_id', $request->user()->id)
            ->with(['product' => function ($query) {
                $query->withTrashed() // Lấy cả sản phẩm bị xoá mềm
                    ->select('id', 'name', 'slug', 'img_thumbnail', 'price_regular', 'price_sale', 'description', 'deleted_at');
            }, 'variant' => function ($query) {
                $query->withTrashed() // Lấy cả biến thể bị xoá mềm
                    ->select('id', 'product_id', 'product_size_id', 'product_color_id', 'quantity', 'deleted_at');
            }])
            ->get();

        // Cập nhật `status` trong bảng `carts` và số lượng nếu sản phẩm hoặc biến thể bị xóa
        $cartItems->each(function ($cartItem) {
            $product = $cartItem->product;
            $variant = $cartItem->variant;

            // Kiểm tra trạng thái sản phẩm (bị xóa mềm)
            if ($product && $product->deleted_at) {
                $cartItem->status = 1;
                $cartItem->quantity = 0;
            } elseif ($variant && $variant->deleted_at) {
                $cartItem->status = 2;
                $cartItem->quantity = 0;
            } else {
                $cartItem->status = 0;
            }

            // Kiểm tra số lượng biến thể có đủ hay không
            if ($variant && $variant->quantity < $cartItem->quantity) {
                $cartItem->quantity = $variant->quantity; // Giảm số lượng về mức tối đa có sẵn trong kho
            }

            $cartItem->save();
        });

        // Tính toán tổng tiền
        $totalPrice = $cartItems->sum(function ($cartItem) {
            // Kiểm tra xem sản phẩm có bị xóa không (status = 0)
            if ($cartItem->status != 0 || !$cartItem->product) {
                return 0;  // Không tính tiền nếu sản phẩm bị xóa hoặc không có sản phẩm
            }
            $price = $cartItem->product->price_sale > 0 ? $cartItem->product->price_sale : $cartItem->product->price_regular;
            return $price * $cartItem->quantity;
        });

        // Trả về danh sách sản phẩm trong giỏ hàng và tổng tiền
        return response()->json([
            'cart_items' => $cartItems->map(function ($cartItem) {
                $product = $cartItem->product;
                $variant = $cartItem->variant;

                // Nếu sản phẩm hoặc biến thể bị xóa, ẩn màu sắc và kích thước
                if ($cartItem->status != 0) {
                    $colorName = null;
                    $sizeName = null;
                } else {
                    // Lấy tên màu sắc và kích thước từ các bảng liên quan
                    $colorName = $variant && $variant->product_color_id
                        ? ProductColor::find($variant->product_color_id)->name
                        : null;
                    $sizeName = $variant && $variant->product_size_id
                        ? ProductSize::find($variant->product_size_id)->name
                        : null;
                }

                // Thêm thông báo về trạng thái sản phẩm
                $statusMessage = '';
                $quantityDisabled = false;

                if ($cartItem->status == 1) {
                    $statusMessage = 'Sản phẩm đã bị xóa khỏi kho';
                    $quantityDisabled = true; // Không cho phép thay đổi số lượng nếu sản phẩm bị xóa
                } elseif ($cartItem->status == 2) {
                    $statusMessage = 'Biến thể sản phẩm đã bị xóa khỏi kho';
                    $quantityDisabled = true; // Không cho phép thay đổi số lượng nếu biến thể bị xóa
                }

                return [
                    'id' => $cartItem->id,
                    'product_id' => $product->id ?? null,
                    'name' => $product->name ?? null,
                    'slug' => $product->slug ?? null,
                    'img_thumbnail' => $product->img_thumbnail ?? null,
                    'quantity' => $cartItem->quantity,
                    'color' => $colorName, // Màu sắc từ tên bảng product_colors, ẩn nếu bị xóa
                    'size' => $sizeName,   // Kích thước từ tên bảng product_sizes, ẩn nếu bị xóa
                    'price_regular' => $product->price_regular ?? 0,
                    'price_sale' => $product->price_sale ?? 0,
                    'price' => $product ? ($product->price_sale > 0 ? $product->price_sale : $product->price_regular) : 0,
                    'total' => $product ? (($product->price_sale > 0 ? $product->price_sale : $product->price_regular) * $cartItem->quantity) : 0,
                    'description' => $product->description ?? null,
                    'status' => $cartItem->status, // Trả về status từ bảng carts
                    'status_message' => $statusMessage, // Thêm thông báo về trạng thái
                    'quantity_disabled' => $quantityDisabled, // Thêm thông tin disable quantity
                ];
            }),
            'total_price' => $totalPrice,
        ], 200);
    }



    /**
     * @OA\Patch(
     *     path="/api/cart/{id}",
     *     operationId="updateCartItemQuantity",
     *     tags={"Cart"},
     *     summary="Cập nhật số lượng sản phẩm trong giỏ hàng",
     *     security={{"Bearer": {}}},
     *     description="Cập nhật số lượng của sản phẩm trong giỏ hàng của người dùng đã đăng nhập.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="integer", example=2, description="Số lượng sản phẩm cần cập nhật"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cập nhật số lượng thành công"),
     *             @OA\Property(property="cart_item", type="object", ref="#/components/schemas/Cart")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Người dùng chưa đăng nhập",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vui lòng đăng nhập")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy sản phẩm trong giỏ hàng",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Sản phẩm không tìm thấy trong giỏ hàng")
     *         )
     *     )
     * )
     */
    public function updateCartItemQuantity(Request $request, $cartItemId)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
        }

        // Xác thực dữ liệu đầu vào
        $validatedData = $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        // Tìm kiếm sản phẩm trong giỏ hàng
        $cartItem = Cart::where('user_id', $request->user()->id)
            ->where('id', $cartItemId)
            ->with('product') // Đảm bảo tải sản phẩm liên kết
            ->first();
Log::info('Cart item: ' . $cartItem);
        if (!$cartItem) {
            return response()->json(['message' => 'Giỏ hàng không tồn tại.'], 404);
        }

        // Kiểm tra xem sản phẩm có tồn tại và không bị xóa mềm
        if (!$cartItem->product || $cartItem->product->deleted_at) {
            return response()->json(['message' => 'Sản phẩm đã bị xóa'], 400);
        }
        $productVariant = ProductVariant::where('id', $cartItem->product_variant_id)->first();
        Log::info('Product variant: ' . $productVariant);
        if (!$productVariant || $productVariant->quantity < $validatedData['quantity']) {
            return response()->json(['message' => 'Số lượng sản phẩm không đủ.'], 400);
        }

        // Kiểm tra xem biến thể có bị xóa không
        if ($productVariant->deleted_at) {
            return response()->json(['message' => 'Biến thể sản phẩm đã bị xóa'], 400);
        }

        // Cập nhật số lượng
        $cartItem->quantity = $validatedData['quantity'];

        $cartItem->save();

        // Tính lại tổng tiền
        $cartItems = Cart::where('user_id', $request->user()->id)->get();
        $totalPrice = $cartItems->sum(function ($item) {
            if (!$item->product) {
                return 0;  // Đảm bảo không có lỗi khi sản phẩm bị thiếu
            }

            $price = $item->product->price_sale > 0 ? $item->product->price_sale : $item->product->price_regular;
            return $price * $item->quantity;
        });

        // Trả về thông tin đã cập nhật
        return response()->json([
            'message' => 'Cập nhật số lượng thành công.',
            'cart_item' => $cartItem,
            'total_price' => $totalPrice
        ], 200);
    }
}
