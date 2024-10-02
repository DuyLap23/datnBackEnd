<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class ResetPassword extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/password/forgot",
     *     summary="Gửi link reset mật khẩu đến email",
     *     description="API để gửi email chứa link reset mật khẩu cho người dùng dựa trên địa chỉ email đã cung cấp.",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Link reset mật khẩu đã được gửi đến email của bạn",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Link reset mật khẩu đã được gửi đến email của bạn")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Email không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Email không hợp lệ"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Không thể gửi link reset mật khẩu",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Không thể gửi link reset mật khẩu")
     *         )
     *     )
     * )
     */
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
                'message' => 'Link reset mật khẩu đã được gửi đến email của bạn'
            ], 200);
        } else {
            return response()->json([
                'message' => 'Không thể gửi link reset mật khẩu'
            ], 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/auth/password/reset",
     *     summary="Đặt lại mật khẩu",
     *     description="API cho phép người dùng đặt lại mật khẩu mới bằng cách cung cấp token, email, và mật khẩu mới.",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token", "email", "password", "password_confirmation"},
     *             @OA\Property(property="token", type="string", example="abcdef123456"),
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mật khẩu đã được đặt lại thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Mật khẩu đã được đặt lại thành công!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dữ liệu không hợp lệ",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Dữ liệu không hợp lệ")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Có lỗi xảy ra khi reset mật khẩu",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Có lỗi xảy ra khi reset mật khẩu.")
     *         )
     *     )
     * )
     */

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
