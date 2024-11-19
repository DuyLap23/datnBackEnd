<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Mail\OrderDeliveredMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderDeliveredEmail
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderDelivered $event): void
    {
        $order = $event->order;
        $userEmail = $order->user->email;

        if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            return; 
        }
        Mail::to($event->order->user->email)->send(new OrderDeliveredMail($event->order));
    }
}
