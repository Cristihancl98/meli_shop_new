<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ACCOUNT_SCOPED_KEYS = ['reputation', 'orders_synced_at'];

    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->text('value')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('value_updated_at')->nullable();
            $table->timestamps();
        });

        $storeLevelRows = DB::table('account_settings')
            ->whereNotIn('key', self::ACCOUNT_SCOPED_KEYS)
            ->orderBy('updated_at')
            ->get()
            ->keyBy('key');

        foreach ($storeLevelRows as $row) {
            DB::table('settings')->insert([
                'key'              => $row->key,
                'value'            => $row->value,
                'is_enabled'       => $row->is_enabled,
                'value_updated_at' => $row->value_updated_at,
                'created_at'       => $row->created_at,
                'updated_at'       => $row->updated_at,
            ]);
        }

        DB::table('account_settings')->whereNotIn('key', self::ACCOUNT_SCOPED_KEYS)->delete();
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
