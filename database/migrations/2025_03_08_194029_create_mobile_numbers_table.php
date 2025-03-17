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
        Schema::create('mobile_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('mobile_number', 11);
            $table->enum('number_type', ['home', 'sms', 'whatsapp']);
            $table->foreignId('student_id');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_numbers');
    }
};
