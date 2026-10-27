<?php

namespace App\Actions\TestCase;

use App\Enums\TestCaseStatus;
use App\Models\Project;
use App\Models\Story;
use App\Models\TestCase;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ImportTestCases
{
    public function __construct(private SaveTestCase $saveTestCase) {}

    /**
     * Create or update test cases from spreadsheet rows. A row whose ID matches an existing
     * test case of the project updates it; any other row creates a new one. Epics and stories
     * are created by name when missing. Invalid rows are skipped and reported.
     *
     * @param  Collection<int, Collection<string, mixed>>  $rows
     * @return array{created: int, updated: int, errors: array<int, string>}
     */
    public function import(Project $project, User $user, Collection $rows): array
    {
        $result = ['created' => 0, 'updated' => 0, 'errors' => []];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $value = fn (string $key): ?string => ($text = trim((string) ($row[$key] ?? ''))) === '' ? null : $text;

            if (collect($row)->filter(fn ($cell) => trim((string) $cell) !== '')->isEmpty()) {
                continue;
            }

            if ($value('title') === null) {
                $result['errors'][] = "Row {$rowNumber}: the title is required.";

                continue;
            }

            $status = $this->parseStatus($value('status'));

            if ($status === false) {
                $result['errors'][] = "Row {$rowNumber}: unknown status \"{$value('status')}\". Use Not run, Passed, Failed or Blocked.";

                continue;
            }

            if ($value('story') !== null && $value('epic') === null) {
                $result['errors'][] = "Row {$rowNumber}: a story needs an epic.";

                continue;
            }

            $data = [
                'title' => Str::limit($value('title'), 255, ''),
                'story_id' => $this->findOrCreateStory($project, $value('epic'), $value('story'))?->id,
                'description' => $value('description'),
                'preconditions' => $value('preconditions'),
                'test_data' => $value('test_data'),
                'environment' => Str::limit((string) $value('environment'), 255, '') ?: null,
                'status' => $status->value,
                'steps' => $this->parseSteps($value('steps'), $value('expected_results')),
            ];

            $existing = $this->findExisting($project, $value('id'));

            if ($existing) {
                $this->saveTestCase->update($existing, $user, $data);
                $result['updated']++;
            } else {
                $this->saveTestCase->create($project, $user, $data);
                $result['created']++;
            }
        }

        return $result;
    }

    private function parseStatus(?string $status): TestCaseStatus|false
    {
        if ($status === null) {
            return TestCaseStatus::NOT_RUN;
        }

        $normalized = Str::of($status)->lower()->replace([' ', '-'], '_')->toString();

        foreach (TestCaseStatus::cases() as $case) {
            if ($normalized === $case->value) {
                return $case;
            }
        }

        return false;
    }

    private function findOrCreateStory(Project $project, ?string $epicTitle, ?string $storyTitle): ?Story
    {
        if ($epicTitle === null || $storyTitle === null) {
            if ($epicTitle !== null) {
                $project->epics()->firstOrCreate(['title' => $epicTitle]);
            }

            return null;
        }

        $epic = $project->epics()->firstOrCreate(['title' => $epicTitle]);

        return $epic->stories()->firstOrCreate(['title' => $storyTitle]);
    }

    private function findExisting(Project $project, ?string $id): ?TestCase
    {
        if ($id === null || ! preg_match('/^(?:tc-)?(\d+)$/i', $id, $matches)) {
            return null;
        }

        return $project->testCases()->where('number', (int) $matches[1])->first();
    }

    /**
     * Split the two multi-line cells into steps, pairing the n-th step with the n-th expected result.
     * Leading numbering such as "1." or "2)" is removed.
     *
     * @return array<int, array{action: string, expected_result: ?string}>
     */
    private function parseSteps(?string $steps, ?string $expectedResults): array
    {
        $lines = fn (?string $text): array => collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn (string $line) => trim(preg_replace('/^\s*\d+\s*[.)\-:]\s*/', '', $line)))
            ->values()
            ->all();

        $actions = $lines($steps);
        $expected = $lines($expectedResults);

        return collect($actions)
            ->map(fn (string $action, int $index) => [
                'action' => $action,
                'expected_result' => ($expected[$index] ?? '') === '' ? null : $expected[$index],
            ])
            ->filter(fn (array $step) => $step['action'] !== '')
            ->values()
            ->all();
    }
}
