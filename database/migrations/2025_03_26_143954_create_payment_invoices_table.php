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
        Schema::create('payment_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('student_id');
            $table->integer('total_amount');
            $table->integer('amount_due');
            $table->string('month_year')->nullable();
            $table->enum('status', ['due', 'partially_paid', 'paid'])->default('due');
            $table->enum('invoice_type', ['tuition_fee', 'model_test_fee', 'exam_fee', 'others_fee'])->default('tuition_fee');
            $table->foreignId('created_by')->nullable();
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
        Schema::dropIfExists('payment_invoices');
    }
};
