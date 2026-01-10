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
        Schema::table('class_names', function (Blueprint $table) {
            $table->string('year_prefix')->nullable()->after('class_numeral');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_names', function (Blueprint $table) {
            $table->dropColumn('year_prefix');
        });
    }
};
