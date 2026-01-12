<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('secondary_class_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id');
            $table->foreignId('secondary_class_id');
            $table->foreignId('invoice_id');

            $table->timestamps();

            // Indexes for faster counting & querying
            $table->index(['student_id', 'secondary_class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secondary_class_payments');
    }
};
