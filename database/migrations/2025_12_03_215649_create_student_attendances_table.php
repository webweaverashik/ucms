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
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('branch_id');
            $table->foreignId('student_id');
            $table->foreignId('class_id');
            $table->foreignId('batch_id');

            // Attendance data
            $table->enum('status', ['present', 'absent', 'late'])->default('present');
            $table->text('remarks')->nullable();
            $table->date('attendance_date');

            $table->foreignId('created_by');
            $table->timestamps();

            // Indexes for performance
            $table->index('attendance_date');
            $table->index(['branch_id', 'class_id', 'batch_id']);
            $table->index(['student_id', 'attendance_date']);

            // Ensure no duplicate attendance for same student on same date
            $table->unique(['student_id', 'attendance_date'], 'student_attendance_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};
