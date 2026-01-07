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
        Schema::create('student_secondary_class_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id');
            $table->foreignId('secondary_class_id');

            // enrolled | dropped
            $table->enum('action', ['enrolled', 'dropped']);

            $table->foreignId('created_by');
            $table->timestamps();

            $table->index(
                ['student_id', 'secondary_class_id'],
                'idx_student_secondary_class_history'
            );
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_secondary_class_histories');
    }
};
