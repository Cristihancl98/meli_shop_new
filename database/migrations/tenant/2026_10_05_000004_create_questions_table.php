<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mercadolibre_account_id')->constrained()->cascadeOnDelete();
            $table->string('meli_question_id', 40)->unique();
            $table->string('meli_item_id', 40)->index();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->text('question');
            $table->string('question_status', 40)->nullable();
            $table->timestamp('asked_at')->nullable();
            $table->text('answer')->nullable();
            $table->string('answer_status', 40)->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->string('buyer_meli_id', 40)->nullable();
            $table->string('buyer_nickname')->nullable();
            $table->boolean('seen')->default(false);
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
