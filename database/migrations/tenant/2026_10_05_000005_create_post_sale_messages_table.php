<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_sale_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mercadolibre_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('pack_id', 40)->index();
            $table->string('meli_message_id', 60)->nullable()->unique();
            $table->text('text');
            $table->boolean('from_seller')->default(false);
            $table->string('status', 40)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->boolean('seen')->default(false);
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_sale_messages');
    }
};
