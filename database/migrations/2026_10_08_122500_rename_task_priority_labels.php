<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $renamedLabels = [
        'Very high' => 'Urgent',
        'Very low' => 'Lowest',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->renamedLabels as $oldLabel => $newLabel) {
            DB::table('task_priorities')->where('label', $oldLabel)->update(['label' => $newLabel]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->renamedLabels as $oldLabel => $newLabel) {
            DB::table('task_priorities')->where('label', $newLabel)->update(['label' => $oldLabel]);
        }
    }
};
