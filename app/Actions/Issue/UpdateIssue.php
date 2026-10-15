<?php

namespace App\Actions\Issue;

use App\Models\Issue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateIssue
{
    /**
     * @param  array{title: string, description?: ?string, steps_to_reproduce?: ?string, expected_result?: ?string, actual_result?: ?string, status: string, severity?: ?string, priority?: ?string, assigned_to_user_id?: ?int, labels?: array<int, int>}  $data
     */
    public function update(Issue $issue, array $data): Issue
    {
        return DB::transaction(function () use ($issue, $data) {
            $issue->update(Arr::except($data, ['labels']));

            if (array_key_exists('labels', $data)) {
                $issue->labels()->sync($data['labels']);
            }

            return $issue;
        });
    }
}
