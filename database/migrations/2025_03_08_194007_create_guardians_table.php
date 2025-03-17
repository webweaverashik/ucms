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
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile_number', 11);
            $table->enum('gender', ['male', 'female']);
            $table->text('address')->nullable();
            $table->enum('relationship', ['father', 'mother', 'brother', 'sister', 'uncle']);
            $table->foreignId('student_id');
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
