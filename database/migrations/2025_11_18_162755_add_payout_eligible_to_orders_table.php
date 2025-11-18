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
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('payout_eligible')->default(false)->after('status');
            $table->timestamp('payout_eligible_at')->nullable()->after('payout_eligible');
            $table->timestamp('return_window_ends_at')->nullable()->after('delivery_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payout_eligible', 'payout_eligible_at', 'return_window_ends_at']);
        });
    }
};
