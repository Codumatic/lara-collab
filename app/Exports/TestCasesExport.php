<?php

namespace App\Exports;

use App\Models\TestCase;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One row per test case. Steps and expected results are numbered lines in one cell each,
 * so the same file can be edited and imported back.
 */
class TestCasesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public const HEADINGS = [
        'ID',
        'Epic',
        'Story',
        'Title',
        'Description',
        'Preconditions',
        'Test data',
        'Environment',
        'Status',
        'Steps',
        'Expected results',
    ];

    /**
     * @param  Collection<int, TestCase>  $testCases
     */
    public function __construct(private Collection $testCases) {}

    /**
     * @return Collection<int, TestCase>
     */
    public function collection(): Collection
    {
        return $this->testCases;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return self::HEADINGS;
    }

    /**
     * @param  TestCase  $testCase
     * @return array<int, string|null>
     */
    public function map($testCase): array
    {
        $numbered = fn (string $column): string => $testCase->steps
            ->map(fn ($step) => "{$step->position}. ".($step->{$column} ?? ''))
            ->implode("\n");

        return [
            "TC-{$testCase->number}",
            $testCase->story?->epic?->title,
            $testCase->story?->title,
            $testCase->title,
            $testCase->description,
            $testCase->preconditions,
            $testCase->test_data,
            $testCase->environment,
            $testCase->status->label(),
            $numbered('action'),
            $numbered('expected_result'),
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A:K')->getAlignment()->setWrapText(true)->setVertical('top');

        return [1 => ['font' => ['bold' => true]]];
    }
}
