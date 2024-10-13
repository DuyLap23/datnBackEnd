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
    Schema::table('addresses', function (Blueprint $table) {
        // Kiểm tra nếu cột Ward tồn tại trước khi đổi tên
        if (Schema::hasColumn('addresses', 'Ward')) {
            $table->renameColumn('Ward', 'ward');
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->renameColumn('ward', 'Ward');
        });
    }
};
