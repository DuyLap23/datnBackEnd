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
        Schema::table('order_items', function (Blueprint $table) {
            // Thêm các cột mới
            $table->unsignedBigInteger('order_id')->after('id'); 
            $table->unsignedBigInteger('product_id')->after('order_id'); 
            $table->integer('quantity')->after('product_id'); 
            $table->decimal('price', 10, 2)->after('quantity'); 
            $table->string('size')->nullable()->after('price'); 
            $table->string('color')->nullable()->after('size'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('order_id');
            $table->dropColumn('product_id');
            $table->dropColumn('quantity');
            $table->dropColumn('price');
            $table->dropColumn('size');
            $table->dropColumn('color');
        });
    }
};
