<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequests;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/users",
     *     summary="Lấy danh sách người dùng",
     *     tags={"User"},
     *     @OA\Response(
     *         response=200,
     *         description="Danh sách người dùng được lấy thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="lấy danh sách người dùng"),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="users", type="array",
     *                 @OA\Items(ref="#/components/schemas/User")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $users = User::query()->with(['addresses' => function ($query) {
            $query->latest('id');
        }])->latest('id')->get();
        return response()->json([
            'message' => 'lấy danh sách người dùng',
            'success' => true,
            'users' => $users,

        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/auth/users/{id}",
     *     summary="Lấy thông tin người dùng",
     *     description="Lấy thông tin chi tiết của người dùng, bao gồm cả địa chỉ liên kết. Chỉ admin hoặc chính người dùng có thể xem thông tin của họ.",
     *     operationId="getUser",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của người dùng",
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lấy thông tin thành công",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lấy thông tin thành công"),
     *             @OA\Property(property="users", type="object", ref="#/components/schemas/User")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Người dùng không có quyền truy cập",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bạn không có quyền xem thông tin của người dùng khác.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Chưa đăng nhập",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bạn cần đăng nhập để xem thông tin.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy người dùng",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không tồn tại người dùng này.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi máy chủ",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Đã xảy ra lỗi không mong muốn.")
     *         )
     *     )
     * )
     */
    public function show(string $id)
    {
        try {
            // Kiểm tra nếu người dùng chưa đăng nhập
            $currentUser = auth('api')->user();
            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn cần đăng nhập để xem thông tin.'
                ], 401); // 401 Unauthorized
            }

            // Kiểm tra nếu người dùng hiện tại là admin hoặc đang xem thông tin của chính mình
            if ($currentUser->id != $id && !$currentUser->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền xem thông tin của người dùng khác.'
                ], 403); // 403 Forbidden
            }

            // Lấy thông tin người dùng và các địa chỉ liên kết, sắp xếp theo id giảm dần
            $user = User::with(['addresses' => function ($query) {
                $query->latest('id');
            }])->findOrFail($id);

            // Trả về phản hồi JSON chứa thông tin người dùng
            return response()->json([
                'message' => 'Lấy thông tin thành công',
                'success' => true,
                'users' => $user,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Nếu không tìm thấy người dùng, trả về lỗi 404
            return response()->json([
                'success' => false,
                'message' => 'Không tồn tại người dùng này.'
            ], 404);
        } catch (\Exception $e) {
            // Nếu có lỗi không mong muốn khác, trả về lỗi 500
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi không mong muốn.'
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/auth/profile",
     *     summary="Lấy thông tin hồ sơ người dùng hiện tại",
     *     tags={"User"},
     *     security={{"Bearer": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Thông tin hồ sơ người dùng hiện tại được lấy thành công",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Người dùng chưa đăng nhập",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bạn cần đăng nhập để xem thông tin.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Người dùng không tìm thấy",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Có lỗi xảy ra",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Error message")
     *         )
     *     )
     * )
     */

    public function profile()
    {
        try {


            $user = auth('api')->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn cần đăng nhập để xem thông tin.'
                ], 401); // 401 Unauthorized
            }

            if ($user) {
                // Load the address relationship
                $user->load(['addresses' => function ($query) {
                    $query->latest('id');
                }]);
                return response()->json($user, 200);
            } else {
                return response()->json(['error' => 'User not found'], 404);
            }
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/auth/profile/update/{id}",
     *     summary="Cập nhật thông tin người dùng",
     *     tags={"User"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của người dùng cần cập nhật",
     *         @OA\Schema(type="bigint")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="Tên người dùng"
     *                 ),
     *                 @OA\Property(
     *                     property="avatar",
     *                     type="file",
     *                     description="Ảnh đại diện của người dùng"
     *                 ),
     *                  @OA\Property(
     *                      property="link_fb",
     *                      type="string",
     *                      description="link facebook"
     *                  ),
     *                  @OA\Property(
     *                      property="link_tt",
     *                      type="string",
     *                      description="link tiktok"
     *                  )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật thông tin thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cập nhật thông tin thành công."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="avatar_url", type="string", example="http://example.com/storage/avatars/avatar.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Chưa đăng nhập",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bạn cần đăng nhập để xem thông tin.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Người dùng không có quyền chỉnh sửa thông tin người khác",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bạn không có quyền chỉnh sửa thông tin của người dùng khác.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không thể thay đổi admin hoặc người dùng không tồn tại",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Không thể thay đổi admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Địa chỉ này đã tồn tại",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Địa chỉ này đã tồn tại.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Dữ liệu không hợp lệ."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Cập nhật thông tin thất bại",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cập nhật thông tin thất bại"),
     *             @OA\Property(property="error", type="string", example="Error message")
     *         )
     *     )
     * )
     */

    public function update(UpdateProfileRequests $request, $id)
    {
        // Lấy người dùng hiện tại từ token Bearer
        $currentUser = auth('api')->user();
        if (!$currentUser) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần đăng nhập để xem thông tin.'
            ], 401); // 401 Unauthorized
        }
        // Kiểm tra xem người dùng hiện tại có phải là người được yêu cầu cập nhật không
        if ($currentUser->id != $id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền chỉnh sửa thông tin của người dùng khác.'
            ], 403); // 403 Forbidden
        }

        DB::beginTransaction();
        try {
            // Lấy người dùng và địa chỉ
            $user = User::findOrFail($id);

            // Kiểm tra nếu người dùng là admin
            if ($user->role == 'admin') {
                return response()->json(['message' => 'Không thể thay đổi admin'], 404);
            }

            // Lấy dữ liệu từ request ngoại trừ avatar
            $data = $request->except('avatar');

            // Xử lý avatar
            if ($request->hasFile('avatar')) {
                $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
                $old_avatar = $user->avatar;
            } else {
                $data['avatar'] = $user->avatar;
            }

            // Cập nhật thông tin người dùng
            $user->fill($data);
            $user->save();

            // Xóa avatar cũ nếu có
            if (isset($old_avatar)) {
                Storage::disk('public')->delete($old_avatar);
            }

            DB::commit();

            $avatarUrl = Storage::disk('public')->url($user->avatar);
            Log::info('Cập nhật người dùng thành công.', ['user_id' => $user->id]);
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin thành công.',
                'data' => [
                    'user' => $user,
                    'avatar_url' => $avatarUrl,
                ]
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['success' => false,
                'message' => 'Không tìm thấy người dùng.',],
                404);
        } catch
        (ValidationException $e) {
            DB::rollBack();
            Log::error('Lỗi khi cập nhật người dùng.', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi cập nhật người dùng.', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật thông tin thất bại',
                'error' => $e->getMessage(),
            ], 500);
        }

    }


    /**
     * @OA\Delete(
     *     path="/api/admin/users/destroy/{id}",
     *     summary="Xóa người dùng",
     *     tags={"User"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của người dùng",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Xóa tài khoản thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Xoá tài khoản thành công")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Người dùng không được tìm thấy",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Người dùng không được tìm thấy")
     *         )
     *     )
     * )
     */
//    public function destroy($id)
//    {
//        $user = User::findOrFail($id);
//
//        if (!$user) {
//            return response()->json(['message' => 'Người dùng không được tìm thấy'], 404);
//        }
//
//        $user->delete();
//
//        return response()->json(['message' => 'Xoá tài khoản thành công'], 204);
//    }




}
