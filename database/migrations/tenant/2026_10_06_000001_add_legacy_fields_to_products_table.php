<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('meli_category_id', 20)->nullable()->after('category_id')->index();
            $table->timestamp('published_at')->nullable()->after('last_sync')->index();
            $table->boolean('description_synced')->default(false)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['meli_category_id']);
            $table->dropIndex(['published_at']);
            $table->dropColumn(['meli_category_id', 'published_at', 'description_synced']);
        });
    }
};
