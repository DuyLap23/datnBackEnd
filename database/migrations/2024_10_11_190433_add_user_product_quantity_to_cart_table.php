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
        Schema::table('carts', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->after('id'); // Thêm cột 'user_id'
            $table->unsignedBigInteger('product_id')->after('user_id'); // Thêm cột 'product_id'
            $table->integer('quantity')->after('product_id'); // Thêm cột 'quantity'
            
            // Nếu muốn thiết lập quan hệ khóa ngoại:
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['product_id']);
            $table->dropColumn('user_id');
            $table->dropColumn('product_id');
            $table->dropColumn('quantity');
        });
    }
};
