<?php

namespace App\Models;

use App\Enums\IssuePriority;
use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Models\Filters\WhereHasFilter;
use App\Models\Filters\WhereInFilter;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lacodix\LaravelModelFilter\Traits\HasFilters;
use LaravelArchivable\Archivable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Issue extends Model implements AuditableContract
{
    use Archivable, Auditable, HasFactory, HasFilters;

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

    /**
     * Columns the list can be sorted by. Enum columns sort by their workflow order, not alphabetically.
     *
     * @var array<int, string>
     */
    public const SORTABLE_COLUMNS = ['number', 'title', 'status', 'severity', 'priority', 'created_at'];

    /**
     * Audit field changes only, not the bookkeeping columns.
     *
     * @var array<int, string>
     */
    protected $auditExclude = ['number', 'project_id', 'reported_by_user_id'];

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

    public function filters(): array
    {
        return [
            (new WhereInFilter('status'))->setQueryName('statuses'),
            (new WhereInFilter('severity'))->setQueryName('severities'),
            (new WhereInFilter('priority'))->setQueryName('priorities'),
            (new WhereInFilter('assigned_to_user_id'))->setQueryName('assignees'),
            (new WhereInFilter('reported_by_user_id'))->setQueryName('reporters'),
            (new WhereHasFilter('labels'))->setQueryName('labels'),
        ];
    }

    /**
     * Search by title or by issue number, written as "12", "#12" or "ISS-12".
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $number = Str::of($term)->replaceMatches('/^(iss-|#)/i', '')->toString();

        return $query->where(function (Builder $query) use ($term, $number) {
            $query->where('title', 'like', "%{$term}%");

            if (ctype_digit($number)) {
                $query->orWhere('number', (int) $number);
            }
        });
    }

    /**
     * Sort by one of the sortable columns, e.g. ['severity' => 'asc'], or fall back to the given default.
     *
     * @param  array<string, string>|null  $sort
     */
    public function scopeSortedBy(Builder $query, ?array $sort, string $defaultColumn = 'number'): Builder
    {
        $column = collect($sort ?? [])->keys()->first();

        if (! in_array($column, self::SORTABLE_COLUMNS, true)) {
            return $query->orderByDesc($defaultColumn);
        }

        $direction = strtolower($sort[$column]) === 'desc' ? 'desc' : 'asc';

        $enumClass = match ($column) {
            'status' => IssueStatus::class,
            'severity' => IssueSeverity::class,
            'priority' => IssuePriority::class,
            default => null,
        };

        if ($enumClass === null) {
            return $query->orderBy($column, $direction);
        }

        $ranks = collect($enumClass::cases())
            ->map(fn ($case, int $rank) => "WHEN '{$case->value}' THEN {$rank}")
            ->implode(' ');

        return $query
            ->orderByRaw("{$column} IS NULL")
            ->orderByRaw("CASE {$column} {$ranks} END {$direction}")
            ->orderByDesc('number');
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
     * The people and labels that appear on the given issues, to fill the filter dropdowns.
     *
     * @return array{assignees: array<int, array{id: int, name: string}>, reporters: array<int, array{id: int, name: string}>, labels: array<int, array{id: int, name: string, color: string}>}
     */
    public static function filterOptionsFor(Builder $issues): array
    {
        $usersFor = fn (string $column): array => User::whereIn('id', (clone $issues)->whereNotNull($column)->select($column))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        $labelIds = DB::table('issue_label')
            ->whereIn('issue_id', (clone $issues)->select('issues.id'))
            ->select('label_id');

        return [
            'assignees' => $usersFor('assigned_to_user_id'),
            'reporters' => $usersFor('reported_by_user_id'),
            'labels' => Label::whereIn('id', $labelIds)->orderBy('name')->get(['id', 'name', 'color'])->toArray(),
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

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCase::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IssueComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(IssueAttachment::class);
    }

    public function isClosed(): bool
    {
        return $this->status === IssueStatus::CLOSED;
    }
}
