<?php

namespace App\Actions\Issue;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateIssue
{
    /**
     * @param  array{title: string, description?: ?string, steps_to_reproduce?: ?string, expected_result?: ?string, actual_result?: ?string, status?: ?string, severity?: ?string, priority?: ?string, assigned_to_user_id?: ?int, labels?: array<int, int>}  $data
     */
    public function create(Project $project, User $reporter, array $data): Issue
    {
        return DB::transaction(function () use ($project, $reporter, $data) {
            $issue = $project->issues()->create([
                ...Arr::except($data, ['labels']),
                'status' => $data['status'] ?? IssueStatus::NEW,
                'reported_by_user_id' => $reporter->id,
            ]);

            $issue->labels()->sync($data['labels'] ?? []);

            return $issue;
        });
    }
}
