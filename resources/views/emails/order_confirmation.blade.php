<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 13px;
            line-height: 18px;
        }
    </style>
</head>

<body>
<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fe/Shopee.svg/1280px-Shopee.svg.png" alt="Shopee Logo" class="logo">

<div style="margin-top: 28px;">
    <p>Xin chào {{ $order->customer_name }},</p>
    <p>Đơn hàng #{{ $order->order_number }} của bạn đã được giao thành công ngày {{ $order->delivery_date->format('d/m/Y') }}.</p>
    <p>
        Vui lòng đăng nhập Shopee để xác nhận bạn đã nhận hàng và hài lòng với sản phẩm trong vòng 3 ngày.
        Sau khi bạn xác nhận, chúng tôi sẽ thanh toán cho Người bán <a href="#" class="text-primary">{{ $order->shop_name }}</a>.
    </p>
    <div>Nếu bạn không xác nhận trong khoảng thời gian này, Shopee cũng sẽ thanh toán cho Người bán.</div>

    <a href="#" class="btn-primary">Đã nhận hàng</a>
</div>

<hr>

<div>
    <h1 style="font-weight: bold; text-transform: uppercase;">THÔNG TIN ĐƠN HÀNG - DÀNH CHO NGƯỜI MUA</h1>
    <table>
        <tr>
            <td>Mã đơn hàng:</td>
            <td><a href="#" class="text-primary">#{{ $order->order_number }}</a></td>
        </tr>
        <tr>
            <td>Ngày đặt hàng:</td>
            <td>{{ $order->created_at->format('d/m/Y H:i:s') }}</td>
        </tr>
        <tr>
            <td>Người bán:</td>
            <td><a href="#" class="text-primary">{{ $order->shop_name }}</a></td>
        </tr>
    </table>

    @foreach($order->items as $item)
        <img src="{{ $item->product_image }}" alt="{{ $item->product_name }}" class="product-image">
        <table>
            <tr>
                <td colspan="2">{{ $loop->iteration }}. {{ $item->product_name }}</td>
            </tr>
            <tr>
                <td>Mẫu mã:</td>
                <td>{{ $item->variant }}</td>
            </tr>
            <tr>
                <td>Số lượng:</td>
                <td>{{ $item->quantity }}</td>
            </tr>
            <tr>
                <td>Giá:</td>
                <td>₫{{ number_format($item->price, 0, ',', '.') }}</td>
            </tr>
        </table>
    @endforeach

    <hr>

    <table>
        <tr>
            <td>Tổng tiền:</td>
            <td>₫{{ number_format($order->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($order->shopee_voucher)
            <tr>
                <td>Voucher từ Shopee:</td>
                <td>₫{{ number_format($order->shopee_voucher, 0, ',', '.') }}</td>
            </tr>
        @endif
        @if($order->shop_voucher)
            <tr>
                <td>Voucher từ Shop:</td>
                <td>₫{{ number_format($order->shop_voucher, 0, ',', '.') }}</td>
            </tr>
        @endif
        <tr>
            <td>Phí vận chuyển:</td>
            <td>₫{{ number_format($order->shipping_fee, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Tổng thanh toán:</td>
            <td>₫{{ number_format($order->total, 0, ',', '.') }}</td>
        </tr>
    </table>
</div>

<hr>

<div>
    <h1 style="font-weight: bold;">BƯỚC TIẾP THEO</h1>
    <p>Bạn không hài lòng về sản phẩm?</p>
    <p>Bạn có thể gửi <a href="#" class="text-primary">yêu cầu trả hàng</a> trên ứng dụng Shopee trong vòng 3 ngày kể từ khi nhận được email này.</p>
    <p>Lưu ý: Shopee sẽ từ chối hỗ trợ các khiếu nại sau khi Người mua nhấn "Đã nhận được hàng" trên ứng dụng và Người bán đã nhận được thanh toán cho đơn hàng.</p>

    <a href="#" class="btn-secondary">Trả hàng/Hoàn tiền</a>
</div>

<div style="margin: 36px 0;">
    <p>Trân trọng,</p>
    <p>Đội ngũ Shopee</p>
    <p style="margin-top: 16px;">Bạn có thắc mắc? Liên hệ chúng tôi <a href="#" class="text-primary">tại đây</a>.</p>
</div>

<div class="footer">
    <p>Hãy mua sắm cùng Shopee</p>
    <div class="social-icons">
        <a href="#"><img src="/images/facebook.png" alt="Facebook" width="40"></a>
        <a href="#"><img src="/images/instagram.png" alt="Instagram" width="40"></a>
        <a href="#"><img src="/images/youtube.png" alt="YouTube" width="40"></a>
    </div>
    <hr>
    <p><a href="#" class="text-primary">Chính sách bảo mật</a> | <a href="#" class="text-primary">Điều khoản Shopee</a></p>
    <p>Đây là email tự động. Vui lòng không trả lời email này. Thêm <a href="mailto:info@mail.shopee.vn" class="text-primary">info@mail.shopee.vn</a> vào danh bạ email của bạn để đảm bảo bạn luôn nhận được email từ chúng tôi.</p>
    <p>Tầng 17 Saigon Centre 2, 67 Đường Lê Lợi, Bến Nghé, Quận 1, Hồ Chí Minh 700000, Vietnam</p>
</div>
</body>

</html>
