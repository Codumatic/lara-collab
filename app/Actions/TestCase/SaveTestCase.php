<?php

namespace App\Actions\TestCase;

use App\Actions\UploadAttachments;
use App\Enums\TestCaseStatus;
use App\Models\Project;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveTestCase
{
    public function __construct(private UploadAttachments $uploadAttachments) {}

    /**
     * @param  array{title: string, story_id?: ?int, description?: ?string, preconditions?: ?string, test_data?: ?string, environment?: ?string, status?: ?string, executed_at?: ?string, steps?: array<int, array{action: string, expected_result?: ?string}>, attachments?: array<int, UploadedFile>}  $data
     */
    public function create(Project $project, User $author, array $data): TestCase
    {
        return DB::transaction(function () use ($project, $author, $data) {
            $testCase = $project->testCases()->make([
                ...$this->fields($data),
                'created_by_user_id' => $author->id,
            ]);

            $this->trackExecution($testCase, $author, $data);
            $testCase->save();

            $this->replaceSteps($testCase, $data['steps'] ?? []);

            if (! empty($data['attachments'])) {
                $this->uploadAttachments->upload(
                    $testCase->attachments(),
                    "test-cases/{$testCase->id}",
                    $author,
                    $data['attachments']
                );
            }

            return $testCase;
        });
    }

    /**
     * @param  array{title: string, story_id?: ?int, description?: ?string, preconditions?: ?string, test_data?: ?string, environment?: ?string, status?: ?string, executed_at?: ?string, steps?: array<int, array{action: string, expected_result?: ?string}>}  $data
     */
    public function update(TestCase $testCase, User $editor, array $data): TestCase
    {
        return DB::transaction(function () use ($testCase, $editor, $data) {
            $testCase->fill($this->fields($data));

            $this->trackExecution($testCase, $editor, $data);
            $testCase->save();

            if (array_key_exists('steps', $data)) {
                $this->replaceSteps($testCase, $data['steps']);
            }

            return $testCase;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function fields(array $data): array
    {
        return [
            ...Arr::only($data, ['title', 'story_id', 'description', 'preconditions', 'test_data', 'environment', 'executed_at']),
            'status' => $data['status'] ?? TestCaseStatus::NOT_RUN,
        ];
    }

    /**
     * A run is recorded when the status moves to passed, failed or blocked; going back to "not run" clears it.
     *
     * @param  array<string, mixed>  $data
     */
    private function trackExecution(TestCase $testCase, User $user, array $data): void
    {
        if ($testCase->status === TestCaseStatus::NOT_RUN) {
            $testCase->executed_at = null;
            $testCase->executed_by_user_id = null;

            return;
        }

        if ($testCase->isDirty('status')) {
            $testCase->executed_by_user_id = $user->id;
            $testCase->executed_at = $data['executed_at'] ?? now();
        }
    }

    /**
     * @param  array<int, array{action: string, expected_result?: ?string}>  $steps
     */
    private function replaceSteps(TestCase $testCase, array $steps): void
    {
        $testCase->steps()->delete();

        $testCase->steps()->createMany(
            collect($steps)->values()->map(fn (array $step, int $index): array => [
                'position' => $index + 1,
                'action' => $step['action'],
                'expected_result' => $step['expected_result'] ?? null,
            ])
        );
    }
}
