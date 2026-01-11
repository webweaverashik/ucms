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
            $table->integer('amount');
            $table->date('enrolled_at')->default(now());
            $table->timestamps();

            // Prevent duplicate active enrollment
            $table->unique(
                ['student_id', 'secondary_class_id'],
                'uq_student_secondary_class'
            );

            $table->index(
                ['secondary_class_id'],
                'idx_secondary_class'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_secondary_classes');
    }
};
