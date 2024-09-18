<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{

    public function register(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ]);
            DB::commit();
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            return response()->json([
                'data' => $user,
                'message' => 'Tạo tài khoản thành công',
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => 'Lỗi tạo sản phẩm thất bại: ' . $e->getMessage()], 500);
        }
    }
}


