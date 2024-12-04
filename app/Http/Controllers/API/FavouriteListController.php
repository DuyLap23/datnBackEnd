<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FavouriteList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;




/**
 * @OA\Schema(
 *     schema="Favourite",
 *     type="object",
 *     title="Favourite",
 *     required={"id", "user_id", "product_id"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID của sản phẩm yêu thích"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64",
 *         description="ID của người dùng"
 *     ),
 *     @OA\Property(
 *         property="product_id",
 *         type="integer",
 *         format="int64",
 *         description="ID của sản phẩm"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Ngày tạo bản ghi"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Ngày cập nhật bản ghi"
 *     )
 * )
 */

class FavouriteListController extends Controller
{
     /**
     * @OA\Get(
     *     path="/api/favourites",
     *     tags={"Favourites"},
     *     summary="Lấy danh sách sản phẩm yêu thích",
     *     security={{"Bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách sản phẩm yêu thích",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Favourite")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vui lòng đăng nhập")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không có sản phẩm yêu thích nào",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Không có sản phẩm yêu thích nào")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $currentUser = auth('api')->user();
        if (!$currentUser) {
            return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
        }

        // Debug user
        \Log::info('Current User', [$currentUser]);

        // Lấy danh sách yêu thích
        $favorites = FavouriteList::where('user_id', $currentUser->id)
            ->whereHas('product', function ($query) {
                $query->whereNull('deleted_at'); // Lọc sản phẩm chưa bị xóa
            })
            ->with(['product' => function ($query) {
                $query->whereNull('deleted_at'); // Lấy chi tiết sản phẩm chưa bị xóa
            }])
            ->get();

        // Debug favourites
        \Log::info('Favourites List', [$favorites]);

        if ($favorites->isEmpty()) {
            return response()->json(['message' => 'Không có sản phẩm yêu thích nào'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $favorites
        ], 200);
    }




    /**
     * @OA\Post(
     *     path="/api/favourites",
     *     tags={"Favourites"},
     *     summary="Thêm sản phẩm vào danh sách yêu thích",
     *     security={{"Bearer": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="product_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Sản phẩm đã được thêm vào yêu thích"),
     * )
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        FavouriteList::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'product_id' => $request->product_id,
            ]
        );

        return response()->json(['message' => 'Sản phẩm đã được thêm vào yêu thích.'], 201);
    }

     /**
     * @OA\Delete(
     *     path="/api/favourites/{id}",
     *     tags={"Favourites"},
     *     summary="Xóa sản phẩm khỏi danh sách yêu thích",
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(response=200, description="Sản phẩm đã được xóa khỏi danh sách yêu thích"),
     * )
     */
    public function destroy(string $id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
        }

        $favorite = FavouriteList::where('user_id', Auth::id())->where('product_id', $id);

        if (!$favorite->exists()) {
            return response()->json(['message' => 'Sản phẩm không nằm trong danh sách yêu thích.'], 404);
        }

        $favorite->delete();

        return response()->json(['message' => 'Sản phẩm đã được xóa khỏi danh sách yêu thích.'],200);
    }
}
