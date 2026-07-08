<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_reviews', function (Blueprint $table) {
            $table->boolean('do_not_review')->default(false)->after('review_date');
            $table->date('review_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_reviews', function (Blueprint $table) {
            $table->dropColumn('do_not_review');
            $table->date('review_date')->nullable(false)->change();
        });
    }
};
