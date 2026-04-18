<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('collection')->nullable()->after('status');
            $table->json('tags')->nullable()->after('collection');
            $table->string('gender')->nullable()->after('tags');
            
            // Add index for better query performance
            $table->index('collection');
            $table->index('gender');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['collection']);
            $table->dropIndex(['gender']);
            $table->dropColumn(['collection', 'tags', 'gender']);
        });
    }
};

