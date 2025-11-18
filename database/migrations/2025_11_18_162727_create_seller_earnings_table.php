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
        Schema::create('seller_earnings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('seller_id');
            $table->uuid('order_id');
            $table->unsignedBigInteger('amount'); // Amount in smallest currency unit (paise)
            $table->unsignedBigInteger('platform_fee')->default(0); // Platform fee deducted
            $table->unsignedBigInteger('net_amount'); // Net amount after fees
            $table->enum('status', ['pending', 'eligible', 'payout_pending', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('eligible_at')->nullable(); // When order became payout-eligible
            $table->timestamp('paid_at')->nullable();
            $table->uuid('payout_batch_item_id')->nullable();
            $table->timestamps();

            $table->foreign('seller_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->index(['seller_id', 'status']);
            $table->index('eligible_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_earnings');
    }
};
