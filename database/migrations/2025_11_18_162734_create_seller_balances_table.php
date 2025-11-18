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
        Schema::create('seller_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('seller_id')->unique();
            $table->unsignedBigInteger('available_balance')->default(0); // Available for payout
            $table->unsignedBigInteger('pending_balance')->default(0); // Pending (not yet eligible)
            $table->unsignedBigInteger('total_earned')->default(0); // Lifetime total
            $table->unsignedBigInteger('total_paid_out')->default(0); // Lifetime total paid
            $table->timestamp('last_payout_at')->nullable();
            $table->timestamps();

            $table->foreign('seller_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_balances');
    }
};
