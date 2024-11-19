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
        Schema::table('product_variants', function (Blueprint $table) {
            $table->softDeletes(); // Thêm cột deleted_at
        });
    }
    
    public function down()
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropSoftDeletes(); // Xóa cột deleted_at nếu rollback
        });
    }
    
};
