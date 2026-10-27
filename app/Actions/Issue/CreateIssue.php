<?php

namespace App\Actions\Issue;

use App\Actions\UploadAttachments;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Notifications\IssueAssignedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateIssue
{
    public function __construct(private UploadAttachments $uploadAttachments) {}

    /**
     * @param  array{title: string, description?: ?string, steps_to_reproduce?: ?string, expected_result?: ?string, actual_result?: ?string, status?: ?string, severity?: ?string, priority?: ?string, assigned_to_user_id?: ?int, labels?: array<int, int>, attachments?: array<int, UploadedFile>}  $data
     */
    public function create(Project $project, User $reporter, array $data): Issue
    {
        $issue = DB::transaction(function () use ($project, $reporter, $data) {
            $issue = $project->issues()->create([
                ...Arr::except($data, ['labels', 'attachments']),
                'status' => $data['status'] ?? IssueStatus::NEW,
                'reported_by_user_id' => $reporter->id,
            ]);

            if (! empty($data['labels'])) {
                $issue->auditSync('labels', $data['labels']);
            }

            if (! empty($data['attachments'])) {
                $this->uploadAttachments->upload($issue->attachments(), "issues/{$issue->id}", $reporter, $data['attachments']);
            }

            return $issue;
        });

        if ($issue->assignedToUser && $issue->assigned_to_user_id !== $reporter->id) {
            $issue->assignedToUser->notify(new IssueAssignedNotification($issue, $reporter));
        }

        return $issue;
    }
}
