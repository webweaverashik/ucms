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
        Schema::create('sheet_topics_taken', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_topic_id');
            $table->foreignId('student_id');
            $table->foreignId('distributed_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sheet_topics_taken');
    }
};
