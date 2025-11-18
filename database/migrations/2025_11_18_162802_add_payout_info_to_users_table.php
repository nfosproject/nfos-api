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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('payout_method', ['bank', 'wallet'])->nullable()->after('status');
            $table->json('payout_details')->nullable()->after('payout_method'); // Bank account or wallet info
            $table->boolean('payout_verified')->default(false)->after('payout_details');
            $table->unsignedBigInteger('payout_threshold')->default(100000)->after('payout_verified'); // Minimum amount for payout (default 1000 NPR)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['payout_method', 'payout_details', 'payout_verified', 'payout_threshold']);
        });
    }
};
