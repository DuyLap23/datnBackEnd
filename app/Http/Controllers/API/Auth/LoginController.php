<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'refresh']]);
    }
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (!($token = auth('api')->attempt($credentials))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $refreshToken = $this->getRefreshToken();

        return $this->respondWithToken($token, $refreshToken);
    }
    public function profile()
    {
        try {
            return response()->json(auth('api')->user());
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
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
