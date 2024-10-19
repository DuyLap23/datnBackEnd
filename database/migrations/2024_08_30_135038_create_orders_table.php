<?php

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->unsigned()->autoIncrement();
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(Address::class)->constrained();
            $table->string('payment_method');
            $table->enum('payment_status', [Order::STARTUS_PAYMENT_UNPAID, Order::STARTUS_PAYMENT_PAID, Order::STARTUS_PAYMENT_FAILED])->default(Order::STARTUS_PAYMENT_UNPAID);
            $table->enum('order_status', [Order::STATUS_ORDER_PENDING,
                Order::STATUS_ORDER_PROCESSING,
                Order::STATUS_ORDER_SHIPPED,
                Order::STATUS_ORDER_SHIPPING,
                Order::STATUS_ORDER_DELIVERED,
                Order::STATUS_ORDER_CANCELLED])
                ->default(Order::STATUS_ORDER_PENDING);
            $table->string('note')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
