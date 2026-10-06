<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku', 100)->nullable()->after('meli_item_id')->index();
            $table->decimal('base_price', 12, 2)->nullable()->after('price');
            $table->decimal('weight', 8, 2)->nullable()->after('base_price');
            $table->json('dimensions')->nullable()->after('weight');
            $table->json('meli_attributes')->nullable()->after('dimensions');
            $table->json('pictures')->nullable()->after('meli_attributes');
            $table->json('variations')->nullable()->after('pictures');
            $table->unsignedInteger('sold_quantity')->default(0)->after('stock');
            $table->foreignId('published_by')->nullable()->after('category_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->enum('status', ['active', 'paused', 'closed', 'under_review', 'inactive'])->default('active')->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('published_by');
            $table->dropIndex(['sku']);
            $table->dropColumn(['sku', 'base_price', 'weight', 'dimensions', 'meli_attributes', 'pictures', 'variations', 'sold_quantity']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->enum('status', ['active', 'paused', 'closed'])->default('active')->change();
        });
    }
};
