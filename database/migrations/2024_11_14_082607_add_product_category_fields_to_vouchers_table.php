<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductCategoryFieldsToVouchersTable extends Migration
{
    public function up()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('applicable_type')->nullable(); // 'product' hoặc 'category'
            $table->json('applicable_ids')->nullable(); // Mảng ID của sản phẩm hoặc danh mục
        });
    }

    public function down()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('applicable_type');
            $table->dropColumn('applicable_ids');
        });
    }
}