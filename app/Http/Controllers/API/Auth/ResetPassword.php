<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class ResetPassword extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        // Xác thực email đầu vào
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        // Gửi email reset password
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => 'success',
                'message' => 'Link reset mật khẩu đã được gửi đến email của bạn'
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể gửi link reset mật khẩu'
            ], 500);
        }
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = bcrypt($password);
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Mật khẩu đã được đặt lại thành công!'], 200);
        }

        return response()->json(['message' => 'Có lỗi xảy ra khi reset mật khẩu.'], 500);
    }
}
