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
    public function index()
    {
        $users = User::query()->with('addresses')->latest('id')->get();
        return response()->json([
            'message' => 'lấy danh sách người dùng',
            'success' => true,
            'users' => $users,

        ], 200);
    }

    public function show(string $id)
    {
        $user = User::query()->findOrFail($id);
        return response()->json([
            'message' => 'Lấy thông tin thành công',
            'success' => true,
            'users' => $user,

        ], 200);
    }

    public function profile()
    {
        try {
            $user = auth('api')->user();
            if ($user) {
                // Load the address relationship
                $user->load('addresses');
                return response()->json($user, 200);
            } else {
                return response()->json(['error' => 'User not found'], 404);
            }
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }


    public function update(UpdateProfileRequests $request, $id)
    {
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
            $user->load('addresses');
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



    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if (!$user) {
            return response()->json(['message' => 'Người dùng không được tìm thấy'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'Xoá tài khoản thành công'], 200);
    }
}
