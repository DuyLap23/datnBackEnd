<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    public function handle($request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Vui lòng đăng nhập'], 401);
        }

        // Lấy thông tin người dùng hiện tại
        $user = Auth::user();

        // Kiểm tra xem vai trò của người dùng có nằm trong danh sách các vai trò cho phép
        if (!in_array($user->role, $roles)) {
            return response()->json(['message' => 'Bạn không có quyền truy cập vào đây.'], 403);
        }

        return $next($request);
    }

}
