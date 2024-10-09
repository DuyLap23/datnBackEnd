<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CartController extends Controller
{
     /**
 * @OA\Post(
 * path="/api/create-store",
 * summary="",
 * description="Tạo mới cửa hàng",
 * tags={"Create Cart"},
 * @OA\RequestBody(
 * required=true,
 * @OA\MediaType(
 * mediaType="application/json",
 * @OA\Schema(
 * @OA\Property(
 * property="name",
 * type="string",
 * description="Giỏ hàng",
 * example="Cửa hàng 1",
 * ),
 * ),
 * ),
 * ),
 * @OA\Response(
 * response=200,
 * description="Thành công",
 * @OA\JsonContent(
 * @OA\Property(
 * property="success",
 * type="boolean",
 * example=true,
 * ),
 * @OA\Property(
 * property="message",
 * type="string",
 * example="Success",
 * ),
 * @OA\Property(
 * property="status",
 * type="string",
 * example="200",
 * ),
 * @OA\Property(
 * property="data",
 * type="object",
 * @OA\Property(
 * property="id",
 * type="integer",
 * example=1,
 * ),
 * @OA\Property(
 * property="name",
 * type="string",
 * example="Cửa hàng 1",
 * ),
 * ),
 * ),
 * ),
 * )
 */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
