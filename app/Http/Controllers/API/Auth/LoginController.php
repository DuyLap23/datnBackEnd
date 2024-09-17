<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'refresh']]);
    }
    public function login()
    {

        $validatedData = request()->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $credentials = request(['email', 'password']);

        // Kiểm tra nếu email không tồn tại
        if (!User::where('email', $credentials['email'])->exists()) {
            return response()->json([
                'error' => 'email_not_found',
                'status' => false,
                'message' => 'Email không tồn tại.'
            ], 401);
        }

        // Kiểm tra nếu thông tin đăng nhập không đúng
        if (!($token = auth('api')->attempt($credentials))) {
            return response()->json([
                'error' => 'invalid_credentials',
                'status' => false,
                'message' => 'Mật khẩu không đúng.'
            ], 401);
        }

        // Tạo refresh token
        $refreshToken = $this->getRefreshToken();

        return $this->respondWithToken($token, $refreshToken);
    }



    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }
    public function refresh()
    {
        $refreshToken = request()->refresh_token;
        try {
            $deCoded = JWTAuth::getJWTProvider()->decode($refreshToken);
            // Xử lý cấp lại token mới
            // Lấy thông tin user

            $user = auth('api')->user();
            if (!$user) {
                throw new \Exception('User not authenticated');
            }

            JWTAuth::invalidate(); // vô hiện hoá token hiện tại

            $token = JWTAuth::login($user); // tạo token mới

            $refreshToken = $this->getRefreshToken();

            return $this->respondWithToken($token, $refreshToken);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    private function respondWithToken($token, $refreshToken)
    {
        return response()->json([
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }
    private function getRefreshToken()
    {
        $data = [
            'user_id' => auth('api')->user()->id,
            'random' => rand() . time(),
            'exp' => time() + config('jwt.refresh_ttl'),
        ];
        $refreshToken = JWTAuth::getJWTProvider()->encode($data);
        return $refreshToken;
    }
}
