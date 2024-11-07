@component('mail::message')
    # Xác nhận đơn hàng #{{ $order->order_code }}

    Cảm ơn bạn đã đặt hàng tại cửa hàng chúng tôi!

    @component('mail::panel')
        **Thông tin đơn hàng:**
        - Ngày đặt: {{ $order->created_at->format('d/m/Y') }}
        - Tổng tiền: {{ number_format($order->total_amount) }}đ
    @endcomponent

    @component('mail::table')
        | Sản phẩm       | Số lượng         | Giá    |
        | -------------- |:----------------:| -------:|
        @foreach($order->items as $item)
            | {{ $item->product->name }} | {{ $item->quantity }} | {{ number_format($item->price) }}đ |
        @endforeach
    @endcomponent

    @component('mail::button', ['url' => route('orders.show', $order->id), 'color' => 'primary'])
        Xem chi tiết đơn hàng
    @endcomponent

    Nếu bạn có bất kỳ thắc mắc nào, vui lòng liên hệ với chúng tôi.

    Cảm ơn,<br>


    @component('mail::subcopy')
        Email này được gửi tự động, vui lòng không trả lời.
    @endcomponent
@endcomponent
