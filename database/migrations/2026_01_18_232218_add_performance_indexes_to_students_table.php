<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * These indexes significantly improve the performance of the students list page
     * which uses server-side DataTables with filtering and searching.
     */
    public function up(): void
    {
        // Add indexes to students table
        Schema::table('students', function (Blueprint $table) {
            // Composite index for the most common query pattern
            $table->index(['student_activation_id', 'branch_id', 'class_id'], 'students_activation_branch_class_idx');
            
            // Index for name search
            $table->index('name', 'students_name_idx');
            
            // Index for student_unique_id search
            $table->index('student_unique_id', 'students_unique_id_idx');
            
            // Index for filtering
            $table->index('gender', 'students_gender_idx');
            $table->index('academic_group', 'students_academic_group_idx');
            $table->index('batch_id', 'students_batch_id_idx');
            $table->index('institution_id', 'students_institution_id_idx');
            
            // Index for ordering
            $table->index('updated_at', 'students_updated_at_idx');
        });

        // Add index to class_names table
        Schema::table('class_names', function (Blueprint $table) {
            $table->index('is_active', 'class_names_is_active_idx');
        });

        // Add index to student_activations table
        Schema::table('student_activations', function (Blueprint $table) {
            $table->index('active_status', 'student_activations_status_idx');
        });

        // Add index to payments table
        Schema::table('payments_info', function (Blueprint $table) {
            $table->index(['student_id', 'payment_style', 'due_date'], 'payments_student_style_due_idx');
        });

        // Add index to mobile_numbers table
        Schema::table('mobile_numbers', function (Blueprint $table) {
            $table->index(['student_id', 'number_type'], 'mobile_numbers_student_type_idx');
            $table->index('mobile_number', 'mobile_numbers_number_idx');
        });

        // Add index to institutions table
        Schema::table('institutions', function (Blueprint $table) {
            $table->index('name', 'institutions_name_idx');
        });

        // Add index to batches table
        Schema::table('batches', function (Blueprint $table) {
            $table->index('name', 'batches_name_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('students_activation_branch_class_idx');
            $table->dropIndex('students_name_idx');
            $table->dropIndex('students_unique_id_idx');
            $table->dropIndex('students_gender_idx');
            $table->dropIndex('students_academic_group_idx');
            $table->dropIndex('students_batch_id_idx');
            $table->dropIndex('students_institution_id_idx');
            $table->dropIndex('students_updated_at_idx');
        });

        Schema::table('class_names', function (Blueprint $table) {
            $table->dropIndex('class_names_is_active_idx');
        });

        Schema::table('student_activations', function (Blueprint $table) {
            $table->dropIndex('student_activations_status_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_student_style_due_idx');
        });

        Schema::table('mobile_numbers', function (Blueprint $table) {
            $table->dropIndex('mobile_numbers_student_type_idx');
            $table->dropIndex('mobile_numbers_number_idx');
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->dropIndex('institutions_name_idx');
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->dropIndex('batches_name_idx');
        });
    }
};