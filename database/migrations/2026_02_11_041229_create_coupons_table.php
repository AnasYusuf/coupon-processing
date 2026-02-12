<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->integer('usage_limit')->default(0);
            $table->integer('per_user_limit')->default(1);
            $table->decimal('min_cart_value', 10, 2)->default(0);
            $table->json('rules')->nullable(); // dynamic rules
            $table->integer('used_count')->default(0);
            $table->string('rule_version')->default('v1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
