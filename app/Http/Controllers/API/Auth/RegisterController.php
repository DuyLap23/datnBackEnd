<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{

    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ]);

            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Tạo tài khoản thành công',

            ], 201);
        }
        catch
        (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo tài khoản.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}


