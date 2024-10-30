<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo thanh toán</title>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            background-color: #f3f4f6;
        }
        .container {
            max-width: 500px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            padding: 2rem;
            text-align: center;
        }
        .container h1 {
            font-size: 24px;
            margin-bottom: 1rem;
            color: #333;
        }
        .container p {
            font-size: 16px;
            color: #555;
            margin-bottom: 1.5rem;
        }
        .status-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .success {
            color: #28a745;
        }
        .error {
            color: #dc3545;
        }
        .button {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 16px;
            color: #fff;
            background-color: #007bff;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<div class="container">
    @if ($status == 'success')
        <div class="status-icon success">✓</div>
        <h1>Thanh toán thành công!</h1>
        <p>Cảm ơn bạn đã thanh toán. Đơn hàng của bạn đã được xác nhận.</p>
    @else
        <div class="status-icon error">✕</div>
        <h1>Thanh toán thất bại!</h1>
        <p>Rất tiếc, giao dịch của bạn không thành công. Vui lòng thử lại hoặc liên hệ hỗ trợ.</p>
    @endif
    <a href="{{ route('home') }}" class="button">Quay về trang chủ</a>
</div>
</body>
</html>
