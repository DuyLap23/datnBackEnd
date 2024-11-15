<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Thêm cột voucher_code sau cột total_amount
            $table->string('voucher_code')->nullable()->after('total_amount');
            
            // Thêm cột voucher_discount sau cột voucher_code
            $table->decimal('voucher_discount', 12, 2)->default(0)->after('voucher_code');
        });
    }
    
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Xóa các cột khi rollback migration
            $table->dropColumn(['voucher_code', 'voucher_discount']);
        });
    }
    
};
