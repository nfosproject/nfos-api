<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('audience')->default('customer'); // customer, admin, seller, all
            $table->string('type')->default('general'); // order_status, product_approval, low_stock, marketing
            $table->string('title');
            $table->text('message');
            $table->string('link')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

