<?php

use App\Models\Product;
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
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');
            $table->foreignIdFor(Product::class)->constrained()->after('user_id');
            $table->string('color')->after('product_id');
            $table->string('size')->after('color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_variant_id')->nullable();
            // Thêm lại khóa ngoại
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
            $table->dropColumn(['color', 'size']);
        });
    }
};
