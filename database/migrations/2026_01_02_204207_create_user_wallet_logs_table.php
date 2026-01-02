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
        Schema::create('user_wallet_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id');

            $table->enum('type', ['collection', 'settlement', 'adjustment']);

            $table->decimal('old_balance', 12, 2);
            $table->decimal('new_balance', 12, 2);
            $table->decimal('amount', 12, 2);

            $table->foreignId('payment_transaction_id')->nullable();

            $table->string('description', 255)->nullable();

            $table->foreignId('created_by');

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_wallet_logs');
    }
};
