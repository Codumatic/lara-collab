<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Closest status for each of the default task group names.
     * Tasks in any other group keep the default "new" status.
     *
     * @var array<string, string>
     */
    private array $statusByGroupName = [
        'backlog' => 'new',
        'todo' => 'new',
        'in progress' => 'in_progress',
        'qa' => 'resolved',
        'deployed' => 'deployed',
        'done' => 'closed',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('status')->default('new')->after('group_id');
        });

        foreach ($this->statusByGroupName as $groupName => $status) {
            $groupIds = DB::table('task_groups')
                ->whereRaw('LOWER(TRIM(name)) = ?', [$groupName])
                ->pluck('id');

            DB::table('tasks')
                ->whereIn('group_id', $groupIds)
                ->update(['status' => $status]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
