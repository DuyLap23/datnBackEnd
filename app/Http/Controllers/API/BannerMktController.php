<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BannerMkt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


/**
 * @OA\Schema(
 *     schema="Banner",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Tiêu đề banner"), 
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
     *     security={{"Bearer": {}}},
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
        if (!Auth::check()) {
            return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
        }

    Log::info('Đang lấy danh sách banner.');
    $banners = BannerMkt::all();
    Log::info('Đã lấy danh sách banner.', ['count' => $banners->count()]);
    return response()->json(['success' => true, 'data' => $banners]);
    }

/**
 * @OA\Post(
 *     path="/api/admin/banners",
 *     tags={"Banners"},
 *     summary="Tạo một banner mới",
 *     security={{"Bearer": {}}},
 *     description="Tạo một banner mới với tiêu đề, hình ảnh và liên kết đã cung cấp.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"name", "image", "link"},
 *                 @OA\Property(property="name", type="string", example="Tiêu đề banner"),
 *                 @OA\Property(property="image", type="string", format="binary"),
 *                 @OA\Property(property="link", type="string", example="http://example.com")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Banner tạo thành công",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Banner đã được tạo thành công"),
 *             @OA\Property(property="data", type="object", ref="#/components/schemas/Banner")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Yêu cầu không hợp lệ",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Dữ liệu không hợp lệ")
 *         )
 *     )
 * )
 */
public function store(Request $request)
{
    if (!Auth::check()) {
        return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
    }

    // Ghi log khi bắt đầu quá trình tạo banner
    Log::info('Bắt đầu quá trình tạo banner.', ['request' => $request->all()]);

    // Xác thực dữ liệu yêu cầu
    $request->validate([
        'name' => 'required|string|max:255',
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        'link' => 'nullable|url',
    ]);

    $banner = new BannerMkt();
    $banner->name = $request->name; 

    // Lưu hình ảnh
    if ($request->hasFile('image')) {
        $banner->image = $request->file('image')->store('banners', 'public'); 
        Log::info('Hình ảnh đã được lưu cho banner.', ['image_path' => $banner->image]);
    }

    $banner->link = $request->link;
    $banner->start_date = now(); 
    $banner->end_date = $request->input('end_date', now()->addDays(7));
    $banner->save(); 
    Log::info('Banner đã được tạo thành công.', ['banner_id' => $banner->id]);

    return response()->json(['success' => true, 'data' => $banner], 201);
}
/**
 * @OA\Post(
 *     path="/api/admin/banners/{id}",
 *     tags={"Banners"},
 *     summary="Cập nhật thông tin một banner",
 *     security={{"Bearer": {}}}, 
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
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"name", "image", "link", "_method"},
 *                 @OA\Property(property="name", type="string", example="Tiêu đề banner"),
 *                 @OA\Property(property="image", type="string", format="binary"),
 *                 @OA\Property(property="link", type="string", example="http://example.com"),
 *                 @OA\Property(property="_method", type="string", example="PUT")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Banner cập nhật thành công",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Banner đã được cập nhật thành công"),
 *             @OA\Property(property="data", type="object", ref="#/components/schemas/Banner")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Yêu cầu không hợp lệ",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Dữ liệu không hợp lệ")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Không tìm thấy banner",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Banner không tồn tại.")
 *         )
 *     )
 * )
 */


 public function update(Request $request, string $id)
{
    if (!Auth::check()) {
        return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
    }

    // Ghi log để kiểm tra toàn bộ request
    Log::info('Dữ liệu yêu cầu (bên trong phương thức update): ', [
        'request_data' => $request->all(),
        'files' => $request->file(),
    ]);

    // Xác thực dữ liệu yêu cầu
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        'link' => 'nullable|url',
    ]);

    // Nếu xác thực thất bại, trả về lỗi với mã 422 (Unprocessable Entity)
    if ($validator->fails()) {
        Log::error('Lỗi xác thực:', ['errors' => $validator->errors()]);
        return response()->json(['success' => false, 'message' => 'Dữ liệu không hợp lệ', 'errors' => $validator->errors()], 422);
    }

    try {
        // Tìm banner theo ID
        $banner = BannerMkt::findOrFail($id);

        // Cập nhật thông tin banner
        $banner->name = $request->name;

        // Lưu hình ảnh mới nếu có
        if ($request->hasFile('image')) {
            // Xóa hình ảnh cũ nếu có
            if ($banner->image) {
                Storage::delete($banner->image);
            }
            // Lưu hình ảnh mới vào thư mục 'banners' của storage
            $banner->image = $request->file('image')->store('banners', 'public');
            Log::info('Hình ảnh đã được cập nhật cho banner.', ['image_path' => $banner->image]);
        }

        // Cập nhật link của banner
        $banner->link = $request->link;

        // Lưu thay đổi vào cơ sở dữ liệu
        $banner->save();

        // Ghi log khi cập nhật thành công
        Log::info('Banner đã được cập nhật thành công.', ['banner_id' => $banner->id]);

        return response()->json([
            'success' => true,
            'message' => 'Banner đã được cập nhật thành công.',
            'data' => $banner
        ]);
    } catch (ModelNotFoundException $e) {
        // Xử lý lỗi không tìm thấy banner
        Log::error('Không tìm thấy banner:', ['banner_id' => $id]);
        return response()->json(['success' => false, 'message' => 'Banner không tồn tại.'], 404);
    } catch (\Exception $e) {
        // Xử lý lỗi hệ thống hoặc cơ sở dữ liệu
        Log::error('Lỗi cập nhật banner:', [
            'error_message' => $e->getMessage(),
            'stack_trace' => $e->getTraceAsString()
        ]);
        return response()->json(['success' => false, 'message' => 'Cập nhật banner không thành công.', 'error' => $e->getMessage()], 500);
    }
}


    /**
     * @OA\Get(
     *     path="/api/admin/banners/{id}",
     *     tags={"Banners"},
     *     summary="Lấy banner theo ID",
     *     security={{"Bearer": {}}},
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
        if (!Auth::check()) {
            return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
        }

        $banner = BannerMkt::findOrFail($id);
        return response()->json(['success' => true, 'data' => $banner]);
    }


    /**
     * @OA\Delete(
     *     path="/api/admin/banners/{id}",
     *     tags={"Banners"},
     *     summary="Xóa một banner",
     *     security={{"Bearer": {}}},
     *     description="Xóa một banner dựa trên ID được cung cấp.",
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
        if (!Auth::check()) {
            return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
        }

        try {
            $banner = BannerMkt::findOrFail($id);
            if ($banner->image) {
                Storage::delete($banner->image);
            }
            $banner->delete();
    
            return response()->json(['success' => true, 'message' => 'Banner đã được xóa thành công.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Banner không tồn tại.'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Xóa banner không thành công.', 'error' => $e->getMessage()], 500);
        }
    }
   /**
 * @OA\Get(
 *     path="/api/home/banners",
 *     tags={"Banners"},
 *     summary="Lấy danh sách banner dành cho trang chủ",
 *     description="Trả về danh sách các banner được hiển thị trên trang chủ.",
 *     @OA\Response(
 *         response=200,
 *         description="Danh sách banner trang chủ",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Banner"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Lỗi khi lấy danh sách banner trang chủ",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Lỗi không xác định.")
 *         )
 *     )
 * )
 */
public function getHomeBanners()
{
    $banners = BannerMkt::where('status', 1)
                        ->limit(5)
                        ->get(['id', 'name', 'image', 'link', 'status', 'start_date', 'end_date', 'created_at', 'updated_at']);
    
    if ($banners->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'Không có banner nào hoạt động',
            'data' => [] 
        ]);
    }
    foreach ($banners as $banner) {
        $banner->image = asset('storage/' . $banner->image);
    }
    return response()->json([
        'success' => true,
        'message' => 'Danh sách banner thành công',
        'data' => $banners 
    ]);
}
}
