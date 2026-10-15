<?php

namespace App\Models;

use App\Enums\IssuePriority;
use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use LaravelArchivable\Archivable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Issue extends Model implements AuditableContract
{
    use Archivable, Auditable, HasFactory;

    protected $fillable = [
        'project_id',
        'reported_by_user_id',
        'assigned_to_user_id',
        'test_case_id',
        'number',
        'title',
        'description',
        'steps_to_reproduce',
        'expected_result',
        'actual_result',
        'status',
        'severity',
        'priority',
    ];

    protected $attributes = [
        'status' => 'new',
    ];

    protected $casts = [
        'number' => 'integer',
        'status' => IssueStatus::class,
        'severity' => IssueSeverity::class,
        'priority' => IssuePriority::class,
    ];

    /**
     * Give every new issue the next number within its project, so it can be shown as ISS-{number}.
     */
    protected static function booted(): void
    {
        static::creating(function (Issue $issue) {
            $issue->number ??= static::withArchived()
                ->where('project_id', $issue->project_id)
                ->max('number') + 1;
        });
    }

    /**
     * Status, severity and priority options for the frontend dropdowns and badges.
     *
     * @return array{statuses: array<int, array{value: string, label: string}>, severities: array<int, array{value: string, label: string}>, priorities: array<int, array{value: string, label: string}>}
     */
    public static function dropdownOptions(): array
    {
        return [
            'statuses' => IssueStatus::options(),
            'severities' => IssueSeverity::options(),
            'priorities' => IssuePriority::options(),
        ];
    }

    /**
     * Limit the query to issues in projects the user has access to.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereIn(
            'project_id',
            PermissionService::projectsThatUserCanAccess($user)->pluck('id')
        );
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reportedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }

    public function isClosed(): bool
    {
        return $this->status === IssueStatus::CLOSED;
    }
}
