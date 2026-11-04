<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Reads every sheet into rows keyed by the slugged headings (id, epic, story, title, ..., expected_results).
 * The rows of each sheet are processed by ImportTestCases.
 */
class TestCasesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        //
    }
}
