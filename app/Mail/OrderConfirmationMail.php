<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $user;

    public function __construct($order, $user)
    {
        $this->order = $order;
        $this->user = $user;

        // Bạn có thể biến đổi một số dữ liệu ở đây nếu cần
        $this->order->delivery_date = now(); // Ví dụ
    }

    public function build()
    {
        return $this->view('emails.order_confirmation')
            ->subject("Đơn hàng #{$this->order->order_number} đã thanh toán thành công")
            ->with([
                'order' => $this->order,
                'user' => $this->user,
            ]);
    }
}
