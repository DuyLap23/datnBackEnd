<?php

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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->autoIncrement();
            $table->string('name'); // Tên của voucher
            $table->decimal('minimum_order_value', 10, 2)->comment("Giá trị đơn hàng tối thiểu"); // Giá trị đơn hàng tối thiểu
            $table->enum('discount_type', ['fixed', 'percent'])->comment(" Giảm theo giá hoặc %")->default('fixed'); // Giảm theo giá hoặc %
            $table->decimal('discount_value', 10, 2) ->comment("Giá trị giảm")  ; // Giá trị giảm
            $table->dateTime('start_date'); // Thời gian bắt đầu áp dụng
            $table->dateTime('end_date'); // Thời gian kết thúc
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher');
    }
};
