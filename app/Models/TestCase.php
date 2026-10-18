<?php

namespace App\Models;

use App\Enums\TestCaseStatus;
use App\Models\Filters\WhereInFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Lacodix\LaravelModelFilter\Traits\HasFilters;

class TestCase extends Model
{
    use HasFilters;

    protected $fillable = [
        'project_id',
        'story_id',
        'created_by_user_id',
        'executed_by_user_id',
        'number',
        'title',
        'description',
        'preconditions',
        'test_data',
        'environment',
        'status',
        'executed_at',
    ];

    protected $attributes = [
        'status' => 'not_run',
    ];

    protected $casts = [
        'number' => 'integer',
        'status' => TestCaseStatus::class,
        'executed_at' => 'datetime',
    ];

    /**
     * Give every new test case the next number within its project, so it can be shown as TC-{number}.
     */
    protected static function booted(): void
    {
        static::creating(function (TestCase $testCase) {
            $testCase->number ??= static::where('project_id', $testCase->project_id)->max('number') + 1;
        });
    }

    public function filters(): array
    {
        return [
            (new WhereInFilter('status'))->setQueryName('statuses'),
        ];
    }

    /**
     * Search by title or by test case number, written as "12", "#12" or "TC-12".
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $number = Str::of($term)->replaceMatches('/^(tc-|#)/i', '')->toString();

        return $query->where(function (Builder $query) use ($term, $number) {
            $query->where('title', 'like', "%{$term}%");

            if (ctype_digit($number)) {
                $query->orWhere('number', (int) $number);
            }
        });
    }

    /**
     * Limit to one story, one epic, or the cases that have no story ("none").
     */
    public function scopeInGroup(Builder $query, ?string $storyId, ?string $epicId): Builder
    {
        if ($storyId === 'none') {
            return $query->whereNull('story_id');
        }

        if ($storyId) {
            return $query->where('story_id', $storyId);
        }

        if ($epicId) {
            return $query->whereHas('story', fn (Builder $query) => $query->where('epic_id', $epicId));
        }

        return $query;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function executedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by_user_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(TestCaseStep::class)->orderBy('position');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TestCaseComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TestCaseAttachment::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }
}
