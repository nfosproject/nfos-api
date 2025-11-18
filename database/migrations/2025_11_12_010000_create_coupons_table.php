<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed_amount', 'free_shipping'])->default('percentage');
            $table->unsignedInteger('value')->default(0);
            $table->unsignedInteger('min_order_amount')->default(0);
            $table->unsignedInteger('max_discount_amount')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_user')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->boolean('is_stackable')->default(false);
            $table->enum('status', ['draft', 'scheduled', 'active', 'expired', 'archived'])->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};

