<?php

use App\Models\Brand;
use App\Models\Category;
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
        Schema::create('products', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->autoIncrement();
            $table->string('name');
            $table->text('slug')->nullable()->comment("đường dẫn sản phẩm");
            $table->string('sku');
            $table->string('img_thumbnail');
            $table->decimal('price_regular', 10, 2);
            $table->decimal('price_sale', 10, 2)->nullable();
            $table->string('description');
            $table->text('content');
            $table->string('user_manual')->comment("Châ liệu");
            $table->tinyInteger('view');
            $table->tinyInteger('is_active');
            $table->tinyInteger('is_new');
            $table->tinyInteger('is_show_home');
            $table->foreignIdFor(Category::class)->constrained();
            $table->foreignIdFor(Brand::class)->constrained();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
