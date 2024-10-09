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
    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Đăng nhập người dùng",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="crawford.kunze@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Đăng nhập thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
     *             @OA\Property(property="refresh_token", type="string", example="refresh_token_here"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Email không tồn tại hoặc mật khẩu không đúng",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Mật khẩu không đúng."),
     *         )
     *     )
     * )
     */
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
                'message' => 'Email không tồn tại.'
            ], 401);
        }

        // Kiểm tra nếu thông tin đăng nhập không đúng
        if (!($token = auth('api')->attempt($credentials))) {
            return response()->json([
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

    /**
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     summary="Refresh token",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", description="JWT refresh token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", description="New JWT access token"),
     *             @OA\Property(property="refresh_token", type="string", description="New JWT refresh token"),
     *             @OA\Property(property="token_type", type="string", example="bearer", description="Type of the token"),
     *             @OA\Property(property="expires_in", type="integer", description="Time in seconds before the token expires")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, user not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", description="Error message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", description="Error message")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */

    public function refresh()
    {
        $refreshToken = request()->refresh_token;
        try {
            $deCoded = JWTAuth::getJWTProvider()->decode($refreshToken);
            // Xử lý cấp lại token mới
            // Lấy thông tin user

            $user = auth('api')->user();
            if (!$user) {
                throw new \Exception('Người dùng chưa được xác thực');
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
