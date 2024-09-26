<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="Comment",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="content", type="string", example="Nội dung bình luận"),
 *     @OA\Property(property="user_id", type="integer", example=123),
 *     @OA\Property(property="post_id", type="integer", example=456),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class CommentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/comments",
     *     tags={"Comments"},
     *     summary="Lấy danh sách tất cả bình luận",
     *     description="Trả về danh sách tất cả các bình luận trong hệ thống.",
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
    public function index()
    {
        $comments = Comment::all();
        return response()->json(['success' => true, 'data' => $comments]);
    }

    /**
     * @OA\Post(
     *     path="/api/comments",
     *     tags={"Comments"},
     *     summary="Tạo một bình luận mới",
     *     description="Tạo một bình luận mới với nội dung, ID người dùng và ID bài viết đã cung cấp.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content", "user_id", "post_id"},
     *             @OA\Property(property="content", type="string", example="Nội dung bình luận"),
     *             @OA\Property(property="user_id", type="integer", example=123),
     *             @OA\Property(property="post_id", type="integer", example=456)
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
            'post_id' => 'required|integer|exists:posts,id',
        ]);

        $comment = new Comment();
        $comment->content = $request->content;
        $comment->user_id = $request->user_id;
        $comment->post_id = $request->post_id;
        $comment->save();

        return response()->json(['success' => true, 'data' => $comment], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/comments/{id}",
     *     tags={"Comments"},
     *     summary="Lấy bình luận theo ID",
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
        $comment = Comment::findOrFail($id);
        return response()->json(['success' => true, 'data' => $comment]);
    }

    /**
     * @OA\Put(
     *     path="/api/comments/{id}",
     *     tags={"Comments"},
     *     summary="Cập nhật một bình luận",
     *     description="Cập nhật nội dung của một bình luận dựa trên ID được cung cấp.",
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
     *             @OA\Property(property="content", type="string", example="Nội dung bình luận đã cập nhật")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bình luận đã được cập nhật thành công",
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

        $comment = Comment::findOrFail($id);
        $comment->content = $request->content;
        $comment->save();

        return response()->json(['success' => true, 'data' => $comment]);
    }

    /**
     * @OA\Delete(
     *     path="/api/comments/{id}",
     *     tags={"Comments"},
     *     summary="Xóa một bình luận",
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
        $comment = Comment::findOrFail($id);
        $comment->delete();

        return response()->json(null, 204);
    }
}
