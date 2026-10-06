<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mercadolibre_account_id')->constrained()->cascadeOnDelete();
            $table->string('key', 60);
            $table->text('value')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('value_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['mercadolibre_account_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_settings');
    }
};
