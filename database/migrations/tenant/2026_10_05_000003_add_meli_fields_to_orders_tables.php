<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('pack_id', 40)->nullable()->after('meli_order_id')->index();
            $table->string('shipping_id', 40)->nullable()->after('shipping_status');
            $table->decimal('final_price', 12, 2)->nullable()->after('total_amount');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('sku', 100)->nullable()->after('meli_item_id');
            $table->string('thumbnail')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['sku', 'thumbnail']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['pack_id']);
            $table->dropColumn(['pack_id', 'shipping_id', 'final_price']);
        });
    }
};
