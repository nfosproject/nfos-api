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
        Schema::create('payout_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('batch_number')->unique();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'partially_failed'])->default('pending');
            $table->unsignedBigInteger('total_amount'); // Total amount in batch
            $table->unsignedInteger('seller_count')->default(0); // Number of sellers in batch
            $table->unsignedInteger('successful_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional batch info
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_batches');
    }
};
