<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('review_status', 20)->nullable()->after('status')->index();
            $table->string('review_reason', 30)->nullable()->after('review_status');
            $table->text('review_detail')->nullable()->after('review_reason');
            $table->decimal('review_amazon_price', 12, 2)->nullable()->after('review_detail');
            $table->timestamp('review_flagged_at')->nullable()->after('review_amazon_price');
            $table->timestamp('amazon_checked_at')->nullable()->after('review_flagged_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['review_status']);
            $table->dropColumn(['review_status', 'review_reason', 'review_detail', 'review_amazon_price', 'review_flagged_at', 'amazon_checked_at']);
        });
    }
};
