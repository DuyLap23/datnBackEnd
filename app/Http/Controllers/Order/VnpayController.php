<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VnpayController extends Controller
{
    public function createPayment(Request $request)
    {
        $vnp_TmnCode = env('VNP_TMNCODE'); // Mã website của bạn tại VNPAY
        $vnp_HashSecret = env('VNP_HASHSECRET'); // Chuỗi bí mật
        $vnp_Url = env('VNP_URL');
        $vnp_Returnurl = env('VNP_RETURNURL');
        $vnp_TxnRef = time(); // Mã đơn hàng, mỗi lần thanh toán phải khác nhau
        $vnp_OrderInfo = $request->order_description;
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = $request->amount * 100; // VNPay yêu cầu số tiền theo đơn vị VND x100
        $vnp_Locale = 'vn'; // Ngôn ngữ, mặc định tiếng Việt
        $vnp_BankCode = $request->bank_code; // Mã ngân hàng (tùy chọn)
        $vnp_IpAddr = $request->ip();

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

        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . $key . "=" . $value;
            } else {
                $hashdata .= $key . "=" . $value;
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash('sha256', $vnp_HashSecret . $hashdata);
            $vnp_Url .= 'vnp_SecureHashType=SHA256&vnp_SecureHash=' . $vnpSecureHash;
        }

        return response()->json(['url' => $vnp_Url]);
    }
    public function paymentReturn(Request $request)
    {
        $vnp_HashSecret = env('VNP_HASHSECRET');
        $vnp_SecureHash = $request->vnp_SecureHash;
        $inputData = array();
        foreach ($request->all() as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }
        unset($inputData['vnp_SecureHashType']);
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $hashData = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . $key . "=" . $value;
            } else {
                $hashData .= $key . "=" . $value;
                $i = 1;
            }
        }
        $secureHash = hash('sha256', $vnp_HashSecret . $hashData);
        if ($secureHash == $vnp_SecureHash) {
            if ($request->vnp_ResponseCode == '00') {
                // Xử lý thanh toán thành công tại đây
                return response()->json(['message' => 'Thanh toán thành công']);
            }
        }
        return response()->json(['message' => 'Thanh toán thất bại']);
    }


}
