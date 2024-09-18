<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{

    public function register(Request $request)
    {
        DB::beginTransaction();

        try {
            // Sử dụng Validator để lấy tất cả lỗi cùng một lúc
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ]);

            // Kiểm tra xem có lỗi validate nào không
            if ($validator->fails()) {
                // Nếu có lỗi, trả về tất cả lỗi
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 400);
            }

            // Tạo người dùng mới sau khi validate thành công
            $user = User::query()->create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Tạo tài khoản thành công',
            ], 201);

        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}


