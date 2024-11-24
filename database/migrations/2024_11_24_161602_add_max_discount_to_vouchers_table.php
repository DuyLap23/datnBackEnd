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
        Schema::table('vouchers', function (Blueprint $table) {
            $table->decimal('max_discount', 10, 2)->nullable()->after('used_count'); 
            // Thay 'column_name' bằng tên cột đứng trước cột mới
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('max_discount');
        });
    }
    
      
    
};
