<?php

namespace App\Events;

use App\Models\Order; 
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderDelivered implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $order; 

    /**
     * Create a new event instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order; 
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('order.' . $this->order->id), 
        ];
    }

    /**
     * Data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'order_code' => $this->order->order_code,
            'user_name' => $this->order->user->name,
            'total_amount' => $this->order->total_amount,
            'status' => $this->order->status,
            'delivery_date' => $this->order->delivered_at,
        ];
    }
}
