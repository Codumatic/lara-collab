<?php

namespace App\Actions\Issue;

use App\Models\Issue;
use App\Models\User;
use App\Notifications\IssueAssignedNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateIssue
{
    /**
     * @param  array{title: string, description?: ?string, steps_to_reproduce?: ?string, expected_result?: ?string, actual_result?: ?string, status: string, severity?: ?string, priority?: ?string, assigned_to_user_id?: ?int, labels?: array<int, int>}  $data
     */
    public function update(Issue $issue, User $editor, array $data): Issue
    {
        $previousAssigneeId = $issue->assigned_to_user_id;

        DB::transaction(function () use ($issue, $data) {
            $issue->update(Arr::except($data, ['labels']));

            if (array_key_exists('labels', $data)) {
                $currentLabelIds = $issue->labels()->pluck('labels.id')->sort()->values()->all();
                $newLabelIds = collect($data['labels'])->map(fn ($id) => (int) $id)->sort()->values()->all();

                if ($currentLabelIds !== $newLabelIds) {
                    $issue->auditSync('labels', $newLabelIds);
                }
            }
        });

        $issue->refresh();

        if (
            $issue->assigned_to_user_id
            && $issue->assigned_to_user_id !== $previousAssigneeId
            && $issue->assigned_to_user_id !== $editor->id
        ) {
            $issue->assignedToUser->notify(new IssueAssignedNotification($issue, $editor));
        }

        return $issue;
    }
}
