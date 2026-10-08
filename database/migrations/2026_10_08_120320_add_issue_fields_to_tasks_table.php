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
        Schema::table('tasks', function (Blueprint $table) {
            $table->text('steps_to_reproduce')->nullable()->after('description');
            $table->text('expected_result')->nullable()->after('steps_to_reproduce');
            $table->text('actual_result')->nullable()->after('expected_result');
            $table->string('severity')->nullable()->after('actual_result');
            $table->string('case_link', 2048)->nullable()->after('severity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'steps_to_reproduce',
                'expected_result',
                'actual_result',
                'severity',
                'case_link',
            ]);
        });
    }
};
