<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class VnpayController extends Controller
{
    public function createPayment(Request $request)
    {
        $latestOrder = Order::latest()->first();
        if (!$latestOrder) {
            return response()->json([
                'code' => '01',
                'message' => 'Không tìm thấy đơn hàng'
            ], 404);
        }
        $data = $request->all();
        $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_Returnurl = "http://127.0.0.1:8000/api/documentation";
        $vnp_TmnCode = "CI8V1FFZ";//Mã website tại VNPAY
        $vnp_HashSecret = "BARL9VV6SEVIY7KNRA179N5DLG3KA6VH"; //Chuỗi bí mật

        $vnp_TxnRef = $latestOrder->id; //Mã đơn hàng. Trong thực tế Merchant cần insert đơn hàng vào DB và gửi mã này

        $vnp_OrderInfo = 'Thanh toán hoá đơn.';
        $vnp_OrderType = "Top Deal shop";
//        $vnp_Amount = $_POST['amount'] * 100;
        $vnp_Amount = $data['total_amount'];
        $vnp_Locale = "VN";
        $vnp_BankCode = "NCB";
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,

        );

        if (isset($vnp_BankCode) && $vnp_BankCode != "") {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }
        if (isset($vnp_Bill_State) && $vnp_Bill_State != "") {
            $inputData['vnp_Bill_State'] = $vnp_Bill_State;
        }

        //var_dump($inputData);
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);//
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }
        $returnData = array('code' => '00'
        , 'message' => 'success'
        , 'data' => $vnp_Url);
        if (isset($_POST['redirect'])) {
            header('Location: ' . $vnp_Url);
            die();
        } else {
            echo json_encode($returnData);
        }

    }
//    public function paymentReturn(Request $request)
//    {
//        $vnp_HashSecret = env('VNP_HASHSECRET');
//        $vnp_SecureHash = $request->vnp_SecureHash;
//        $inputData = array();
//        foreach ($request->all() as $key => $value) {
//            if (substr($key, 0, 4) == "vnp_") {
//                $inputData[$key] = $value;
//            }
//        }
//        unset($inputData['vnp_SecureHashType']);
//        unset($inputData['vnp_SecureHash']);
//        ksort($inputData);
//        $hashData = "";
//        $i = 0;
//        foreach ($inputData as $key => $value) {
//            if ($i == 1) {
//                $hashData .= '&' . $key . "=" . $value;
//            } else {
//                $hashData .= $key . "=" . $value;
//                $i = 1;
//            }
//        }
//        $secureHash = hash('sha256', $vnp_HashSecret . $hashData);
//        if ($secureHash == $vnp_SecureHash) {
//            if ($request->vnp_ResponseCode == '00') {
//                // Xử lý thanh toán thành công tại đây
//                return response()->json(['message' => 'Thanh toán thành công']);
//            }
//        }
//        return response()->json(['message' => 'Thanh toán thất bại']);
//    }


}
