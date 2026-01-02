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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('current_balance', 12, 2)->default(0.00)->after('is_active');
            $table->decimal('total_collected', 12, 2)->default(0.00)->after('current_balance');
            $table->decimal('total_settled', 12, 2)->default(0.00)->after('total_collected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['current_balance', 'total_collected', 'total_settled']);
        });
    }
};
