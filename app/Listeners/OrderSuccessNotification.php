<?php

namespace App\Listeners;

use App\Events\OrderSuccess;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class OrderSuccessNotification implements ShouldQueue
{
    use InteractsWithQueue;
    /**
     * Create the event listener.
     */
    public function __construct()
    {

    }
    /**
     * Handle the event.
     */
    public function handle(OrderSuccess $event)
    {

        $order = $event->order;
        $user = $event->user;
        Mail::to($user->email)->send(new OrderConfirmation($order));
    }
}
