<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the enum type to include 'referral' and 'review'
        DB::statement("ALTER TABLE user_points MODIFY COLUMN type ENUM('earn', 'redeem', 'expire', 'adjust', 'referral', 'review') DEFAULT 'earn'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum
        DB::statement("ALTER TABLE user_points MODIFY COLUMN type ENUM('earn', 'redeem', 'expire', 'adjust') DEFAULT 'earn'");
    }
};
