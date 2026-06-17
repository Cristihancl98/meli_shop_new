<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('meli_item_id')->unique()->nullable();
            $table->foreignId('mercadolibre_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->integer('stock')->default(0);
            $table->enum('status', ['active', 'paused', 'closed'])->default('active');
            $table->string('condition')->default('new');
            $table->string('listing_type_id')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('permalink')->nullable();
            $table->timestamp('last_sync')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
