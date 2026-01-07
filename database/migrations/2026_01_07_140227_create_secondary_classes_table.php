<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Secondary / Special classes
     * Examples: English Class, Model Test
     * Belongs to ONE regular class
     */
    public function up(): void
    {
        Schema::create('secondary_classes', function (Blueprint $table) {
            $table->id();

            // Parent regular class (Class 04–12)
            $table->foreignId('class_id');

            $table->string('name'); // English Class, Model Test

            // Payment behavior
            $table->enum('payment_type', ['one_time', 'monthly']);
            $table->integer('fee_amount');

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable();
            $table->timestamps();

            $table->index(['class_id', 'is_active']);
            $table->index('payment_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secondary_classes');
    }
};
