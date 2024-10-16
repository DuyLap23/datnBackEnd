<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(
 *     schema="Comment",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="content", type="string", example="Nội dung bình luận"),
 *     @OA\Property(property="user_id", type="integer", example=123),
 *     @OA\Property(property="product_id", type="integer", example=456),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class CommentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/comments",
     *     tags={"Admin Comments"},
     *     summary="Lấy danh sách tất cả bình luận",
     *      security={{"Bearer": {}}},
     *     description="Trả về danh sách tất cả các bình luận trong hệ thống hoặc lọc theo sản phẩm và người dùng.",
     *     @OA\Parameter(
     *         name="product_id",
     *         in="query",
     *         description="ID sản phẩm để lọc bình luận",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="ID người dùng để lọc bình luận",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách bình luận",
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

        // Lọc theo product_id và user_id nếu có
        $query = Comment::query();

        if ($request->has('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $comments = $query->get();

        return response()->json(['success' => true, 'data' => $comments]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/comments",
     *      tags={"Admin Comments"},
     *     summary="Tạo một bình luận mới",
     *      security={{"Bearer": {}}},
     *     description="Tạo một bình luận mới với nội dung, ID người dùng và ID sản phẩm đã cung cấp.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content", "user_id", "product_id"},
     *             @OA\Property(property="content", type="string", example="Nội dung bình luận"),
     *             @OA\Property(property="user_id", type="integer", example=123),
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
            'user_id' => 'required|integer|exists:users,id',
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $comment = new Comment();
        $comment->content = $request->content;
        $comment->user_id = $request->user_id;
        $comment->product_id = $request->product_id;
        $comment->save();

        return response()->json(['success' => true, 'data' => $comment], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/comments/{id}",
     *     tags={"Admin Comments"},
     *     summary="Lấy bình luận theo ID",
     *      security={{"Bearer": {}}},
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
            $comment = Comment::findOrFail($id);
            return response()->json(['success' => true, 'data' => $comment]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Bình luận không tồn tại.'], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/comments/{id}", 
     *      tags={"Admin Comments"},
     *     summary="Cập nhật một bình luận",
     *      security={{"Bearer": {}}},
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
            $comment = Comment::findOrFail($id);
            $comment->content = $request->content;
            $comment->save();

            return response()->json(['success' => true, 'data' => $comment]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Bình luận không tồn tại.'], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/comments/{id}",
     *      tags={"Admin Comments"},
     *     summary="Xóa bình luận",
     *      security={{"Bearer": {}}},
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
            $comment = Comment::findOrFail($id);
            $comment->delete();

            return response()->json(['success' => true, 'message' => 'Bình luận đã được xóa thành công.'], 204);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Bình luận không tồn tại.'], 404);
        }
    }
}
