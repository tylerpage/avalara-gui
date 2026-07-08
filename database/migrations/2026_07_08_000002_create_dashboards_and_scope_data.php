<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        $now = now();

        DB::table('dashboards')->insert([
            'name' => 'Default',
            'slug' => 'default',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Schema::table('integration_settings', function (Blueprint $table) {
            $table->foreignId('dashboard_id')->default(1)->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('integration_settings', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->unique(['dashboard_id', 'key']);
        });

        Schema::table('order_reviews', function (Blueprint $table) {
            $table->foreignId('dashboard_id')->default(1)->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('order_reviews', function (Blueprint $table) {
            $table->dropUnique(['shopware_order_id']);
            $table->unique(['dashboard_id', 'shopware_order_id']);
        });

        Schema::table('webhook_events', function (Blueprint $table) {
            $table->foreignId('dashboard_id')->default(1)->after('id')->constrained()->cascadeOnDelete();
            $table->index(['dashboard_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dashboard_id');
        });

        Schema::table('order_reviews', function (Blueprint $table) {
            $table->dropUnique(['dashboard_id', 'shopware_order_id']);
            $table->dropConstrainedForeignId('dashboard_id');
            $table->unique('shopware_order_id');
        });

        Schema::table('integration_settings', function (Blueprint $table) {
            $table->dropUnique(['dashboard_id', 'key']);
            $table->dropConstrainedForeignId('dashboard_id');
            $table->unique('key');
        });

        Schema::dropIfExists('dashboards');
    }
};
