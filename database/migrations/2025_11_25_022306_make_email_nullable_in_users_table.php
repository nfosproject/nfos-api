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
        // For MySQL, we need to drop the unique index first, modify the column, then add it back
        Schema::table('users', function (Blueprint $table) {
            // Drop the unique constraint on email
            $table->dropUnique(['email']);
        });

        // Modify the column to be nullable
        DB::statement('ALTER TABLE `users` MODIFY `email` VARCHAR(255) NULL');

        // Add back the unique constraint (MySQL allows multiple NULLs in unique columns)
        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, update any NULL emails to a placeholder (to avoid constraint violation)
        DB::table('users')->whereNull('email')->update(['email' => DB::raw("CONCAT('temp_', id, '@temp.com')")]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        DB::statement('ALTER TABLE `users` MODIFY `email` VARCHAR(255) NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};
