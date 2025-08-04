<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change ENUM definition to include new values
        DB::statement("ALTER TABLE payment_invoices 
            MODIFY invoice_type ENUM('tuition_fee', 'model_test_fee', 'exam_fee', 'others_fee', 'sheet_fee', 'diary_fee', 'book_fee') 
            DEFAULT 'tuition_fee'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional: revert to old values (if needed)
        DB::statement("ALTER TABLE payment_invoices 
            MODIFY invoice_type ENUM('tuition_fee', 'model_test_fee', 'exam_fee', 'others_fee', 'sheet_fee') 
            DEFAULT 'tuition_fee'");
    }
};
