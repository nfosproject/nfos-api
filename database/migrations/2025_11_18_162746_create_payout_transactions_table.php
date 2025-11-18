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
        Schema::create('payout_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payout_batch_item_id');
            $table->uuid('seller_id');
            $table->unsignedBigInteger('amount');
            $table->string('transaction_id')->unique(); // External transaction ID
            $table->enum('status', ['initiated', 'processing', 'completed', 'failed', 'reversed'])->default('initiated');
            $table->string('payout_method'); // 'bank', 'wallet', etc.
            $table->json('payout_details')->nullable(); // Account details used
            $table->text('error_message')->nullable();
            $table->json('response_data')->nullable(); // Full API response
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('payout_batch_item_id')->references('id')->on('payout_batch_items')->cascadeOnDelete();
            $table->foreign('seller_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['seller_id', 'status']);
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_transactions');
    }
};
