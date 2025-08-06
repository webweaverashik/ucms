<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSiblingsTableColumns extends Migration
{
    public function up()
    {
        // Rename 'age' to 'year' if it exists
        if (Schema::hasColumn('siblings', 'age')) {
            Schema::table('siblings', function (Blueprint $table) {
                $table->renameColumn('age', 'year');
            });
        }

        // Drop foreign key and 'institution_id' column
        if (Schema::hasColumn('siblings', 'institution_id')) {
            try {
                Schema::table('siblings', function (Blueprint $table) {
                    $table->dropForeign(['institution_id']);
                });
            } catch (\Exception $e) {
                // Foreign key doesn't exist, safe to ignore
            }

            Schema::table('siblings', function (Blueprint $table) {
                $table->dropColumn('institution_id');
            });
        }

        // Add new column 'institution_name'
        Schema::table('siblings', function (Blueprint $table) {
            $table->string('institution_name')->nullable()->after('class');
        });
    }

    public function down()
    {
        // Rename 'year' back to 'age' if it exists
        if (Schema::hasColumn('siblings', 'year')) {
            Schema::table('siblings', function (Blueprint $table) {
                $table->renameColumn('year', 'age');
            });
        }

        // Drop 'institution_name' and re-add 'institution_id' as foreign key
        if (Schema::hasColumn('siblings', 'institution_name')) {
            Schema::table('siblings', function (Blueprint $table) {
                $table->dropColumn('institution_name');
            });
        }

        Schema::table('siblings', function (Blueprint $table) {
            $table->foreignId('institution_id')->nullable()->constrained()->after('class');
        });
    }
}
