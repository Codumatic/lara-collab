<?php

namespace App\Http\Controllers\Issue;

use App\Enums\IssuePriority;
use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\Project;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use OwenIt\Auditing\Models\Audit;

class IssueHistoryController extends Controller
{
    /**
     * Readable names for the audited columns.
     *
     * @var array<string, string>
     */
    private const FIELD_LABELS = [
        'title' => 'Title',
        'description' => 'Description',
        'steps_to_reproduce' => 'Steps to reproduce',
        'expected_result' => 'Expected result',
        'actual_result' => 'Actual result',
        'status' => 'Status',
        'severity' => 'Severity',
        'priority' => 'Priority',
        'assigned_to_user_id' => 'Assignee',
        'test_case_id' => 'Linked case',
        'archived_at' => 'Archived',
        'labels' => 'Labels',
    ];

    /**
     * Rich text fields are too long to show inline, so the history only says they changed.
     *
     * @var array<int, string>
     */
    private const LONG_FIELDS = ['description', 'steps_to_reproduce'];

    /**
     * List every change made to the issue, newest first.
     */
    public function index(Project $project, Issue $issue): JsonResponse
    {
        $this->authorize('view', [$issue, $project]);

        $audits = $issue->audits()->with('user')->latest()->get();

        $userIds = $audits
            ->flatMap(fn (Audit $audit) => [
                $audit->old_values['assigned_to_user_id'] ?? null,
                $audit->new_values['assigned_to_user_id'] ?? null,
            ])
            ->filter()
            ->unique();

        $userNames = User::whereIn('id', $userIds)->pluck('name', 'id');

        return response()->json(
            $audits->map(fn (Audit $audit): array => [
                'id' => $audit->id,
                'event' => $audit->event,
                'user' => $audit->user?->only(['id', 'name', 'avatar']),
                'created_at' => $audit->created_at,
                'changes' => $audit->event === 'created'
                    ? []
                    : $this->changes($audit, $userNames->all()),
            ])->values()
        );
    }

    /**
     * @param  array<int, string>  $userNames
     * @return array<int, array{field: string, from: ?string, to: ?string, long: bool}>
     */
    private function changes(Audit $audit, array $userNames): array
    {
        $fields = array_unique([...array_keys($audit->old_values ?? []), ...array_keys($audit->new_values ?? [])]);

        return collect($fields)
            ->filter(fn (string $field) => array_key_exists($field, self::FIELD_LABELS))
            ->map(fn (string $field): array => [
                'field' => self::FIELD_LABELS[$field],
                'from' => $this->readable($field, $audit->old_values[$field] ?? null, $userNames),
                'to' => $this->readable($field, $audit->new_values[$field] ?? null, $userNames),
                'long' => in_array($field, self::LONG_FIELDS, true),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $userNames
     */
    private function readable(string $field, mixed $value, array $userNames): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        return match ($field) {
            'status' => IssueStatus::tryFrom($value)?->label() ?? $value,
            'severity' => IssueSeverity::tryFrom($value)?->label() ?? $value,
            'priority' => IssuePriority::tryFrom($value)?->label() ?? $value,
            'assigned_to_user_id' => $userNames[$value] ?? 'Unknown user',
            'test_case_id' => ($testCase = TestCase::find($value)) ? "TC-{$testCase->number}" : 'Deleted test case',
            'archived_at' => 'Yes',
            'labels' => collect($value)->pluck('name')->sort()->implode(', '),
            default => is_scalar($value) ? (string) $value : null,
        };
    }
}
