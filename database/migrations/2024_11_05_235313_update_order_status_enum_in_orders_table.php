<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY order_status ENUM('pending', 'processing', 'shipping', 'delivered', 'received', 'completed', 'cancelled', 'failed', 'rescheduled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'received', 'completed', 'cancelled', 'failed', 'rescheduled') DEFAULT 'pending'");
    }
};
