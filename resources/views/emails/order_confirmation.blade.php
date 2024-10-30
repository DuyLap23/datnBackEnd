<!DOCTYPE html>
<html>
<head>
    <title>Đặt hàng thành công</title>
</head>
<body>
<h1>Cảm ơn bạn đã đặt hàng!</h1>
<p>Chi tiết đơn hàng của bạn:</p>
<ul>
    <li>Mã đơn hàng: {{ $order->id }}</li>
    <li>Tổng giá: {{ $order->total }}</li>
    <!-- Thêm thông tin khác nếu cần -->
</ul>
</body>
</html>
