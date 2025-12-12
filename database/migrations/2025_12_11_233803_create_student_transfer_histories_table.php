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
        Schema::create('student_transfer_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id');
            $table->foreignId('from_branch_id');
            $table->foreignId('to_branch_id');
            $table->foreignId('from_batch_id');
            $table->foreignId('to_batch_id');
            $table->foreignId('transferred_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_transfer_histories');
    }
};
