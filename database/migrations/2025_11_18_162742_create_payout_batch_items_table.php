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
        Schema::create('payout_batch_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payout_batch_id');
            $table->uuid('seller_id');
            $table->unsignedBigInteger('amount'); // Amount to payout
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('payout_method')->nullable(); // 'bank', 'wallet', etc.
            $table->string('transaction_id')->nullable(); // External transaction ID
            $table->text('error_message')->nullable();
            $table->json('payout_details')->nullable(); // Bank account, wallet info, etc.
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('payout_batch_id')->references('id')->on('payout_batches')->cascadeOnDelete();
            $table->foreign('seller_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['payout_batch_id', 'status']);
            $table->index('seller_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_batch_items');
    }
};
