<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserCommentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/user/comments",
     *     tags={"User Comments"},
     *     summary="Lấy danh sách bình luận của người dùng",
     *     security={{"Bearer": {}}},
     *     description="Trả về danh sách bình luận của người dùng đã đăng nhập.",
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách bình luận của người dùng",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Comment"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi khi lấy danh sách bình luận",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lỗi không xác định.")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Lấy danh sách bình luận của người dùng đã đăng nhập
        $userId = Auth::id();
        $comments = Comment::where('user_id', $userId)->get();

        return response()->json(['success' => true, 'data' => $comments]);
    }

    /**
     * @OA\Post(
     *     path="/api/user/comments",
     *     tags={"User Comments"},
     *     summary="Tạo bình luận mới",
     *     security={{"Bearer": {}}},
     *     description="Tạo một bình luận mới cho sản phẩm đã cung cấp.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content", "product_id"},
     *             @OA\Property(property="content", type="string", example="Nội dung bình luận"),
     *             @OA\Property(property="product_id", type="integer", example=456)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Bình luận được tạo thành công",
     *         @OA\JsonContent(ref="#/components/schemas/Comment")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Dữ liệu đầu vào không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Dữ liệu không hợp lệ.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi khi tạo bình luận",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tạo bình luận không thành công.")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:500',
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $comment = new Comment();
        $comment->content = $request->content;
        $comment->user_id = Auth::id(); 
        $comment->product_id = $request->product_id;
        $comment->save();

        return response()->json(['success' => true, 'data' => $comment], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/user/comments/{id}",
     *     tags={"User Comments"},
     *     summary="Lấy bình luận theo ID",
     *     security={{"Bearer": {}}},
     *     description="Lấy thông tin chi tiết của một bình luận dựa trên ID được cung cấp.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của bình luận cần lấy",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Phản hồi thành công",
     *         @OA\JsonContent(ref="#/components/schemas/Comment")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy bình luận",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bình luận không tồn tại.")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $comment = Comment::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
            return response()->json(['success' => true, 'data' => $comment]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Bình luận không tồn tại.'], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/user/comments/{id}",
     *     tags={"User Comments"},
     *     summary="Cập nhật bình luận",
     *     security={{"Bearer": {}}},
     *     description="Cập nhật bình luận dựa trên ID đã cung cấp và nội dung mới.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của bình luận cần cập nhật",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", example="Nội dung bình luận đã cập nhật"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bình luận được cập nhật thành công",
     *         @OA\JsonContent(ref="#/components/schemas/Comment")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy bình luận",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bình luận không tồn tại.")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|max:500',
        ]);

        try {
            $comment = Comment::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
            $comment->content = $request->content;
            $comment->save();

            return response()->json(['success' => true, 'data' => $comment]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Bình luận không tồn tại.'], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/user/comments/{id}",
     *     tags={"User Comments"},
     *     summary="Xóa bình luận",
     *     security={{"Bearer": {}}},
     *     description="Xóa một bình luận dựa trên ID được cung cấp.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của bình luận cần xóa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Bình luận đã được xóa thành công"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy bình luận",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bình luận không tồn tại.")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $comment = Comment::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
            $comment->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Bình luận không tồn tại.'], 404);
        }
    }
}
