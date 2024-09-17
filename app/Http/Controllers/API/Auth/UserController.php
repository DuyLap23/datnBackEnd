<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
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
        $users = User::query()->with('address')->get();
        return response()->json(['users' => $users], 200);
    }

    public function profile()
    {
        try {
            return response()->json(auth('api')->user());
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = User::query()->with('addresses')->findOrFail($id);

          if ($user->role == 'admin') {
                return response()->json(['message' => 'Không thể thay đổi admin'], 404);
            }

            $validated = $request->validate([

            ]);

            if (isset($validated['avatar'])) {
                $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
                $old_avatar = $user->avatar;

            }else{
                $validated['avatar'] = $user->avatar;

            }

            // Cập nhật thông tin người dùng
            $user->fill($validated);
            $user->save(); // Lưu thông tin người dùng trước

//            Xoá aảnh cũ
            if (isset($old_avatar)) {
                Storage::disk('public')->delete($old_avatar);
            }

            // Cập nhật hoặc tạo địa chỉ
            if (isset($validated['addresses'])) {
                foreach ($validated['addresses'] as $addressData) {
                    if (isset($addressData['id'])) {
                        // Cập nhật địa chỉ nếu có id
                        $address = $user->addresses()->find($addressData['id']);
                        if ($address) {
                            $address->update($addressData);
                        }
                    } else {
                        // Tạo mới địa chỉ nếu không có id
                        $user->addresses()->create($addressData);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin thành công.',
                'data' => $user
            ], 200);
        } catch(Exception $e) {
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
