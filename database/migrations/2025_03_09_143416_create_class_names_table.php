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
        Schema::create('class_names', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Class levels (IV, V, IX, HSC)
            $table->string('class_numeral'); // Three => 03, Four => 04, helpful for student id generation
            $table->foreignId('branch_id');
            $table->softDeletes(); // Enables soft delete feature
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_names');
    }
};
