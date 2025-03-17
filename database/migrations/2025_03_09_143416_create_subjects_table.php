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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('subject_name');
            $table->enum('academic_group', ['General', 'Science', 'Commerce', 'Arts']);
            $table->boolean('is_mandatory')->default(false); // 1 = Auto-selected, 0 = Optional
            $table->foreignId('class_id');
            $table->softDeletes(); // Enables soft delete feature
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
