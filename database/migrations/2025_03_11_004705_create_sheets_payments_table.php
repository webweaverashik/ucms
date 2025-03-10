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
        Schema::create('sheets_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_id');
            $table->foreignId('student_id');
            $table->integer('amount_paid'); // Partial or full payment
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sheets_payments');
    }
};
