<?php

use App\Models\User;
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
        Schema::create('addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->autoIncrement();
            $table->foreignIdFor(User::class)->constrained();
            $table->string('address_name')->nullable();
            $table->integer('phone_number');
            $table->string('city');
            $table->string('district')->comment('Huyện');
            $table->string('Ward')->comment('Xã/Phường');
            $table->string('detail_address')->nullable();
            $table->tinyInteger('is_default')->default(0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
