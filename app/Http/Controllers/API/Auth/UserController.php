<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()->with('address')->get();
        return response()->json(['users' => $users], 200);
    }

//    public function update(Request $request, $id)
//    {
//        try {
//            $user = User::query()->with('address')->findOrFail($id);
//
//            if (!$user) {
//                return response()->json(['message' => 'Người dùng không được tìm thấy'], 404);
//            } elseif ($user->role == 'admin') {
//                return response()->json(['message' => 'Không thể thay đổi admin'], 404);
//            }
//
//            $validated = $request->validate([
//                'name' => 'string|max:255',
//                'avatar' => ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
//                'link_fb' => 'string|max:255',
//                'link_tt' => 'string|max:255',
//                'address_name' => ['string', 'max:255', 'nullable'],
//                'phone_number' => ['required', 'string|max:255'],
//                'city' => ['required', 'string', 'max:255'],
//                'district' => ['required', 'string', 'max:255'],
//                'ward' => ['required', 'string', 'max:255',],
//                'detail_address' => ['required', 'string', 'max:255'],
//                'is_default' => ['boolean', 'nullable'],
//            ]);
//            if(isset($validated['avatar'])) {
//
//            }
//
//
//            $user->save();
//
//            return response()->json($user, 200);
//        } catch (Exception $e) {
//            DB::rollBack();
//            return response()->json([
//                'success' => false,
//                'message' => 'Cập nhật thông tin thất bại',
//                'error' => $e->getMessage()
//            ], 500);
//        }
//    }

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
