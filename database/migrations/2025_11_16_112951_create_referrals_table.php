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
        Schema::create('referrals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('referrer_id'); // User who referred
            $table->uuid('referred_id'); // User who was referred
            $table->string('referral_code');
            $table->boolean('points_awarded')->default(false);
            $table->timestamp('points_awarded_at')->nullable();
            $table->timestamps();

            $table->foreign('referrer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('referred_id')->references('id')->on('users')->cascadeOnDelete();
            
            $table->unique(['referrer_id', 'referred_id']);
            $table->index('referral_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
