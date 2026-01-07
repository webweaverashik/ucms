<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Current active secondary class enrollments
     * (NOT history)
     */
    public function up(): void
    {
        Schema::create('student_secondary_classes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id');

            $table->foreignId('secondary_class_id');

            $table->date('enrolled_at')->default(now());
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();

            // Prevent duplicate enrollment
            $table->unique(['student_id', 'secondary_class_id']);
            $table->index(['secondary_class_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_secondary_classes');
    }
};
