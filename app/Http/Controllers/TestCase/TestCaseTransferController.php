<?php

namespace App\Http\Controllers\TestCase;

use App\Actions\TestCase\ImportTestCases;
use App\Exports\TestCasesExport;
use App\Http\Controllers\Controller;
use App\Imports\TestCasesImport;
use App\Models\Project;
use App\Models\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TestCaseTransferController extends Controller
{
    /**
     * Download the test cases of the current view (same group, search and status filters) as .xlsx.
     */
    public function export(Request $request, Project $project): BinaryFileResponse
    {
        $this->authorize('viewAny', [TestCase::class, $project]);

        $testCases = $project->testCases()
            ->with(['story.epic', 'steps'])
            ->inGroup($request->input('story'), $request->input('epic'))
            ->search($request->input('search'))
            ->filterByQueryString()
            ->orderBy('number')
            ->get();

        return Excel::download(
            new TestCasesExport($testCases),
            Str::slug($project->name).'-test-cases-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    /**
     * An empty sheet with the expected columns, to fill in and import.
     */
    public function template(Project $project): BinaryFileResponse
    {
        $this->authorize('create', [TestCase::class, $project]);

        return Excel::download(new TestCasesExport(collect()), 'test-cases-template.xlsx');
    }

    public function import(Request $request, Project $project, ImportTestCases $importTestCases): RedirectResponse
    {
        $this->authorize('create', [TestCase::class, $project]);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $rows = Excel::toCollection(new TestCasesImport, $request->file('file'))->first() ?? collect();

        $result = $importTestCases->import($project, $request->user(), $rows);

        $summary = "Added {$result['created']}, updated {$result['updated']}.";

        if ($result['errors'] !== []) {
            $skipped = count($result['errors']);
            $details = collect($result['errors'])->take(5)->implode(' ');
            $more = $skipped > 5 ? ' And '.($skipped - 5).' more.' : '';

            return redirect()->back()->error('Import finished with problems', "{$summary} Skipped {$skipped} rows. {$details}{$more}");
        }

        return redirect()->back()->success('Import finished', $summary);
    }
}
