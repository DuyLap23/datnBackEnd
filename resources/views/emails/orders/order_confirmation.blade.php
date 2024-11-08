<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đơn hàng</title>
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #333333;
            margin: 0;
            padding: 0;
            width: 100% !important;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        .container {
            text-align: center;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header img {
            width: 100px;
            margin: 0 auto;
            display: block;
        }
        .content h1 {
            font-size: 18px;
            color: #333333;
            margin-bottom: 20px;
            text-align: center;
        }
        .content p {
            font-size: 14px;
            line-height: 1.5;
            color: #555555;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            color: #ffffff;
            background-color: #ee4d2d;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            margin: 20px auto;
        }

        .order-info, .product-info {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .order-info td, .product-info th, .product-info td {
            padding: 10px;
            border: 1px solid #dddddd;
            text-align: left;
        }
        .product-info th {
            background-color: #f9f9f9;
        }
        .footer p {
            font-size: 12px;
            color: #666666;
            text-align: center;
        }
    </style>
</head>

<body>
<div class="container">
    <div class="header">
{{--        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fe/Shopee.svg/1280px-Shopee.svg.png" alt="Shopee Logo">--}}
    </div>

    <div class="content">
        <h1>Xác nhận đơn hàng {{ $order->order_code }}</h1>
        <p>Xin chào {{ $order->user->name }},</p>
        <p>Cảm ơn bạn đã đặt hàng tại cửa hàng của chúng tôi. Dưới đây là thông tin chi tiết về đơn hàng của bạn:</p>

        <table class="order-info">
            <tr>
                <td><strong>Mã đơn hàng:</strong> {{ $order->order_code }}</td>
                <td><strong>Ngày đặt:</strong> {{ $order->created_at->format('d/m/Y') }}</td>
                <td><strong>Tổng tiền:</strong> {{ number_format($order->total_amount) }}đ</td>
            </tr>
        </table>

        <table class="product-info">
            <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Số lượng</th>
                <th>Giá</th>
            </tr>
            </thead>
            <tbody>
            @foreach($order->orderItems as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->price) }}đ</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div>
            <p>Nếu bạn có bất kỳ thắc mắc nào, vui lòng liên hệ với chúng tôi qua email hoặc hotline hỗ trợ.</p>
            <a href="#" class="button">Xem đơn hàng</a>
        </div>
    </div>

    <div class="footer">
        <p>Email này được gửi tự động, vui lòng không trả lời.</p>
        <p>Trân trọng</p>
    </div>
</div>
</body>
</html>
