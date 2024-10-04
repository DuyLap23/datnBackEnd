<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\StoreRequests;
use App\Models\VouCher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VouCherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $voucher  = VouCher::query()->with("categories")->latest('id')->paginate(10);
            return response()->json([
                'message' => 'Lấy thành công tất cả voucher',
                'success' => true,
                'data' => $voucher,
            ], 200);
        } catch (\Exception $exception) {

            return response()->json([
                'message' => 'lỗi voucher',
                'success' => false,
                'error' => $exception->getMessage(),
            ], 404);
        }
    }

    /**
     * Show the form for creating a new resource.
     */

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequests $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->all();
            $voucher = VouCher::query()->create($data);
            DB::commit();
            return response()->json([
                'message' => 'Thêm voucher thành công',
                'success' => true,
                'data' => $voucher,
            ], 200);

        }   catch (ModelNotFoundException $exception) {

            DB::rollBack();
            return response()->json([
                'message' => 'Thêm voucher thất bại',
                'success' => false,
                'error' => $exception->getMessage(),
            ], 404);
        }
        catch (\Exception $exception) {

            DB::rollBack();
            return response()->json([
                'message' => 'Thêm voucher thất bại',
                'success' => false,
                'error' => $exception->getMessage(),
            ], 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(VouCher $vouCher)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VouCher $vouCher)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VouCher $vouCher)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VouCher $vouCher)
    {
        //
    }
}
