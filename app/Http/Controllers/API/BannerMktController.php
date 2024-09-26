<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BannerMkt;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="Banner",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Tiêu đề banner"),
 *     @OA\Property(property="image", type="string", example="link/to/image.jpg"),
 *     @OA\Property(property="link", type="string", example="http://example.com"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class BannerMktController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/banners",
     *     tags={"Banners"},
     *     summary="Lấy danh sách tất cả banner",
     *     description="Trả về danh sách tất cả các banner trong hệ thống.",
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách banner",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Banner"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi khi lấy danh sách banner",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lỗi không xác định.")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $banners = BannerMkt::all();
        return response()->json(['success' => true, 'data' => $banners]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/banners",
     *     tags={"Banners"},
     *     summary="Tạo một banner mới",
     *     description="Tạo một banner mới với tiêu đề, hình ảnh và liên kết đã cung cấp.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "image"},
     *             @OA\Property(property="title", type="string", example="Tiêu đề banner"),
     *             @OA\Property(property="image", type="string", format="binary", example="link/to/image.jpg"),
     *             @OA\Property(property="link", type="string", example="http://example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Banner được tạo thành công",
     *         @OA\JsonContent(ref="#/components/schemas/Banner")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Dữ liệu đầu vào không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Dữ liệu không hợp lệ."),
     *             @OA\Property(property="errors", type="array", @OA\Items(type="string", example={"Tiêu đề là bắt buộc", "Hình ảnh không hợp lệ"}))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi khi tạo banner",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tạo banner không thành công."),
     *             @OA\Property(property="error", type="string", example="Lý do lỗi")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'link' => 'nullable|url',
        ]);
    
        $banner = new BannerMkt();
        $banner->title = $request->title;
        
        // Lưu hình ảnh
        if ($request->hasFile('image')) {
            $banner->image = $request->file('image')->store('banners');
        }
    
        $banner->link = $request->link;
        $banner->save();
    
        return response()->json(['success' => true, 'data' => $banner], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/banners/{id}",
     *     tags={"Banners"},
     *     summary="Lấy banner theo ID",
     *     description="Lấy thông tin chi tiết của một banner dựa trên ID được cung cấp.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của banner cần lấy",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Phản hồi thành công",
     *         @OA\JsonContent(ref="#/components/schemas/Banner")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy banner",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Banner không tồn tại.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi khi lấy banner",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lấy banner không thành công."),
     *             @OA\Property(property="error", type="string", example="Lý do lỗi")
     *         )
     *     )
     * )
     */
    public function show(string $id)
    {
        $banner = BannerMkt::findOrFail($id);
        return response()->json(['success' => true, 'data' => $banner]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/banners/{id}",
     *     tags={"Banners"},
     *     summary="Cập nhật một banner",
     *     description="Cập nhật thông tin của một banner dựa trên ID được cung cấp.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của banner cần cập nhật",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "image"},
     *             @OA\Property(property="title", type="string", example="Tiêu đề banner đã cập nhật"),
     *             @OA\Property(property="image", type="string", format="binary", example="link/to/image.jpg"),
     *             @OA\Property(property="link", type="string", example="http://example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Banner đã được cập nhật thành công",
     *         @OA\JsonContent(ref="#/components/schemas/Banner")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy banner",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Banner không tồn tại.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi khi cập nhật banner",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cập nhật banner không thành công."),
     *             @OA\Property(property="error", type="string", example="Lý do lỗi")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'link' => 'nullable|url',
        ]);
    
        $banner = BannerMkt::findOrFail($id);
        $banner->title = $request->title ?? $banner->title;
        
        // Cập nhật hình ảnh
        if ($request->hasFile('image')) {
            $banner->image = $request->file('image')->store('banners');
        }
    
        $banner->link = $request->link ?? $banner->link;
        $banner->save();
    
        return response()->json(['success' => true, 'data' => $banner]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/banners/{id}",
     *     tags={"Banners"},
     *     summary="Xóa một banner",
     *     description="Xóa banner dựa trên ID được cung cấp.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của banner cần xóa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Banner đã được xóa thành công"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy banner",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Banner không tồn tại.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi khi xóa banner",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Xóa banner không thành công."),
     *             @OA\Property(property="error", type="string", example="Lý do lỗi")
     *         )
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $banner = BannerMkt::findOrFail($id);
        $banner->delete();
    
        return response()->json(null, 204);
    }
}
