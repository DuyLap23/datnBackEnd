<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequests;
use App\Models\Address;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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
     *     tags={"User"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của người dùng",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thông tin người dùng được lấy thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lấy thông tin thành công"),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="users", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Người dùng không tìm thấy",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Người dùng không tìm thấy")
     *         )
     *     )
     * )
     */
    public function show(string $id)
    {
        // Lấy người dùng hiện tại từ token Bearer
        $currentUser = auth('api')->user();

        // Kiểm tra xem người dùng hiện tại có phải là người được yêu cầu cập nhật không
        if ($currentUser->id != $id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xem thông tin của người dùng khác.'
            ], 403); // 403 Forbidden
        }
        $user = User::query()->with(['addresses' => function ($query) {
            $query->latest('id');
        }])->findOrFail($id);
        return response()->json([
            'message' => 'Lấy thông tin thành công',
            'success' => true,
            'users' => $user,

        ], 200);
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
            return response()->json(['error' => $th->getMessage()], 500);
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
     *         description="ID của người dùng",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/UpdateProfileRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cập nhật thông tin thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cập nhật thông tin thành công."),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không thể thay đổi admin",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Không thể thay đổi admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Có lỗi xảy ra",
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
            $user = User::query()->with('addresses')->findOrFail($id);

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

            // Kiểm tra nếu `is_default` là true, cập nhật các địa chỉ khác thành không mặc định
            if ($request->is_default) {
                Address::query()->where('user_id', $user->id)->update(['is_default' => false]);
            }

            // Kiểm tra xem địa chỉ đã tồn tại chưa
            $existingAddress = Address::where([
                ['user_id', '=', $user->id],
                ['ward', '=', $request->ward],
                ['detail_address', '=', $request->detail_address],
            ])->first();

            if ($existingAddress) {
                return response()->json([
                    'success' => false,
                    'message' => 'Địa chỉ này đã tồn tại.',
                ], 409); // 409 Conflict
            }

            // Cập nhật hoặc tạo mới địa chỉ
            Address::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'ward' => $request->ward,
                    'detail_address' => $request->detail_address,
                ],
                [
                    'address_name' => $request->address_name,
                    'phone_number' => $request->phone_number,
                    'city' => $request->city,
                    'district' => $request->district,
                    'ward' => $request->ward,
                    'is_default' => $request->is_default,
                ]
            );
            $user->load(['addresses' => function ($query) {
                $query->latest('id');
            }]);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin thành công.',
                'data' => [$user]
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật thông tin thất bại',
                'error' => $e->getMessage()
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

    /**
     * @OA\Delete(
     *     path="/api/auth/addresses/destroy/{id}",
     *     summary="Xóa địa chỉ của người dùng",
     *     tags={"User"},
     *     security={{"Bearer": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID của địa chỉ cần xóa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Xóa địa chỉ thành công"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Không thể xóa địa chỉ mặc định"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Người dùng không tồn tại hoặc địa chỉ không tồn tại"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Có lỗi xảy ra"
     *     ),
     * )
     */
    public function destroyAddress($id)
    {
        try {
            // Lấy thông tin người dùng hiện tại
            $user = auth('api')->user();

            // Kiểm tra xem người dùng có tồn tại không
            if (!$user) {
                return response()->json(['message' => 'Người dùng không tồn tại!'], 404);
            }

            // Tìm địa chỉ theo ID và kiểm tra thuộc về người dùng
            $address = Address::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            // Kiểm tra nếu địa chỉ không tồn tại
            if (!$address) {
                return response()->json(['message' => 'Địa chỉ không tồn tại!'], 404);
            }

            // Kiểm tra nếu địa chỉ là mặc định
            if ($address->is_default) {
                return response()->json(['message' => 'Không thể xoá địa chỉ mặc định'], 403);
            }

            // Xóa địa chỉ
            $address->delete();

            return response()->json(null, 204);
        } catch (Exception $e) {
            return response()->json(['message' => 'Có lỗi xảy ra', 'error' => $e->getMessage()], 500);
        }
    }





}
