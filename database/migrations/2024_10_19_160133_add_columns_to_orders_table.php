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
        Schema::table('orders', function (Blueprint $table) {
            // Thêm các cột mới
            $table->unsignedBigInteger('user_id')->after('id'); 
            $table->decimal('total_amount', 10, 2)->after('user_id'); 
            $table->unsignedBigInteger('address_id')->after('total_amount'); 
            $table->string('payment_method')->after('address_id'); 
            $table->string('payment_status')->after('payment_method'); 
            $table->string('order_status')->after('payment_status'); 
            $table->text('note')->nullable()->after('order_status'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Xóa các cột nếu rollback
            $table->dropColumn('user_id');
            $table->dropColumn('total_amount');
            $table->dropColumn('address_id');
            $table->dropColumn('payment_method');
            $table->dropColumn('payment_status');
            $table->dropColumn('order_status');
            $table->dropColumn('note');
        });
    }
};
