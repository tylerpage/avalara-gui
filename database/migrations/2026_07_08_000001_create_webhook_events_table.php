<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('shopware_event_id')->nullable()->index();
            $table->string('event_name')->index();
            $table->string('source_url')->nullable();
            $table->string('shopware_shop_id')->nullable();
            $table->boolean('is_return_related')->default(false)->index();
            $table->string('shopware_order_id')->nullable()->index();
            $table->string('shopware_order_number')->nullable()->index();
            $table->string('shopware_return_id')->nullable()->index();
            $table->json('payload');
            $table->json('headers')->nullable();
            $table->timestamp('received_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
