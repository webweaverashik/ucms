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
        Schema::create('costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id');
            $table->date('cost_date');
            $table->foreignId('created_by');
            $table->timestamps();

            // Unique constraint: one cost per date per branch
            $table->unique(['branch_id', 'cost_date']);

            // Index for faster queries
            $table->index(['cost_date', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('costs');
    }
};
