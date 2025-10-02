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
        Schema::table('students', function (Blueprint $table) {
            $table->index('branch_id');
            $table->index('class_id');
            $table->index('batch_id');
            $table->index('academic_group');
            $table->index('deleted_at');
            $table->index(['class_id', 'academic_group', 'batch_id'], 'students_class_group_batch_index'); // custom name to avoid default name collision
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropIndex(['branch_id']);
                $table->dropIndex(['class_id']);
                $table->dropIndex(['batch_id']);
                $table->dropIndex(['academic_group']);
                $table->dropIndex(['deleted_at']);
                $table->dropIndex('students_class_group_batch_index');
            });
        });
    }
};
