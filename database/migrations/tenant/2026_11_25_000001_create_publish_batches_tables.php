<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publish_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mercadolibre_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('publish_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publish_batch_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 100);
            $table->string('status', 20)->default('queued')->index();
            $table->json('data')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('meli_item_id', 40)->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamps();

            $table->unique(['publish_batch_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publish_batch_items');
        Schema::dropIfExists('publish_batches');
    }
};
