<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mercadolibre_account_id')->constrained()->cascadeOnDelete();
            $table->date('stat_date');
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->decimal('average_ticket', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['mercadolibre_account_id', 'stat_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_statistics');
    }
};
