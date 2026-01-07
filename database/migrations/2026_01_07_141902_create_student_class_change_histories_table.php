<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Student regular class change history
     * Stores ONLY class change events
     */
    public function up(): void
    {
        Schema::create('student_class_change_histories', function (Blueprint $table) {
            $table->id();

            // Student whose class was changed
            $table->foreignId('student_id');

            // Previous class
            $table->foreignId('from_class_id');

            // New class
            $table->foreignId('to_class_id');

            // Who performed the change (admin / manager)
            $table->foreignId('created_by');

            $table->timestamps();

            $table->index(['student_id', 'from_class_id', 'to_class_id'], 'idx_student_class_change');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_class_change_histories');
    }
};
