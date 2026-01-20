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
        // Add indexes to sheet_payments table
        Schema::table('sheet_payments', function (Blueprint $table) {
            // Individual indexes for foreign keys (improves JOIN and WHERE performance)
            $table->index('sheet_id', 'idx_sheet_payments_sheet_id');
            $table->index('invoice_id', 'idx_sheet_payments_invoice_id');
            $table->index('student_id', 'idx_sheet_payments_student_id');

            // Composite index for common query patterns
            $table->index(['student_id', 'sheet_id'], 'idx_sheet_payments_student_sheet');

            // Index for soft deletes (often used in WHERE clause)
            $table->index('deleted_at', 'idx_sheet_payments_deleted_at');

            // Index for ordering by created_at (common in pagination)
            $table->index('created_at', 'idx_sheet_payments_created_at');

            // Composite index for soft delete + created_at (common query pattern)
            $table->index(['deleted_at', 'created_at'], 'idx_sheet_payments_deleted_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sheet_payments', function (Blueprint $table) {
            $table->dropIndex('idx_sheet_payments_sheet_id');
            $table->dropIndex('idx_sheet_payments_invoice_id');
            $table->dropIndex('idx_sheet_payments_student_id');
            $table->dropIndex('idx_sheet_payments_student_sheet');
            $table->dropIndex('idx_sheet_payments_deleted_at');
            $table->dropIndex('idx_sheet_payments_created_at');
            $table->dropIndex('idx_sheet_payments_deleted_created');
        });
    }
};
