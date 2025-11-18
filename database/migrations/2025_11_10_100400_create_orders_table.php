<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('seller_id')->nullable();
            $table->string('order_number')->unique();
            $table->enum('status', ['pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('tax_total')->default(0);
            $table->unsignedBigInteger('shipping_total')->default(0);
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('seller_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
