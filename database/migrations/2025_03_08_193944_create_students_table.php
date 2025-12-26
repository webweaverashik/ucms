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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_unique_id')->unique();
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('name');
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->foreignId('class_id')->constrained('class_names');
            $table->enum('academic_group', ['General', 'Science', 'Commerce', 'Arts'])->default('General');
            $table->foreignId('batch_id')->constrained('batches');
            $table->foreignId('institution_id')->nullable()->constrained('institutions');
            $table->string('religion')->nullable();
            $table->string('blood_group')->nullable();
            $table->text('home_address')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password');
            $table->foreignId('reference_id')->nullable()->constrained('references');
            $table->foreignId('student_activation_id')->nullable()->constrained('student_activations'); // for setting approval status
            $table->string('photo_url')->nullable();
            $table->text('remarks')->nullable();
            $table->softDeletes(); // Enables soft delete feature
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
