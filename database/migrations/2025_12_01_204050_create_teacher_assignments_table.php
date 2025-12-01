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
        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id');
            $table->foreignId('branch_id');
            $table->foreignId('batch_id');
            $table->foreignId('class_id');
            $table->foreignId('subject_id');
            $table->timestamps();

            $table->unique(['teacher_id', 'class_id', 'branch_id', 'batch_id', 'subject_id'], 'unique_teacher_assignment');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_assignments');
    }
};
