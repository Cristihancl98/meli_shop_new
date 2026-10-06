<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_publish_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mercadolibre_account_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 100);
            $table->enum('status', ['pending', 'published', 'error'])->default('pending');
            $table->timestamps();

            $table->index(['mercadolibre_account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_publish_codes');
    }
};
