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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id');
            $table->string('student_classname');
            $table->foreignId('payment_invoice_id');
            $table->enum('payment_type', ['partial', 'full', 'discounted']);
            $table->integer('amount_paid');
            $table->integer('remaining_amount');
            $table->string('voucher_no')->unique();
            $table->text('remarks')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
