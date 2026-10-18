<?php

namespace App\Http\Controllers\TestCase;

use App\Actions\TestCase\SaveTestCase;
use App\Enums\TestCaseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\TestCase\SaveTestCaseRequest;
use App\Http\Resources\TestCase\TestCaseResource;
use App\Models\Project;
use App\Models\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TestCaseController extends Controller
{
    /**
     * The epic/story tree on the left and the test cases of the selected group on the right.
     * When a test case is given, it opens in the drawer.
     */
    public function index(Request $request, Project $project, ?TestCase $testCase = null): Response
    {
        $this->authorize('viewAny', [TestCase::class, $project]);

        if ($testCase) {
            $this->authorize('view', [$testCase, $project]);
        }

        $epics = $project->epics()
            ->orderBy('title')
            ->with(['stories' => fn ($query) => $query->orderBy('title')->withCount('testCases')])
            ->get(['id', 'project_id', 'title', 'description']);

        return Inertia::render('Projects/TestCases/Index', [
            'project' => $project,
            'epics' => $epics,
            'counts' => [
                'all' => $project->testCases()->count(),
                'withoutStory' => $project->testCases()->whereNull('story_id')->count(),
            ],
            'items' => TestCaseResource::collection(
                $project->testCases()
                    ->with(['story.epic:id,title', 'executedByUser:id,name,avatar'])
                    ->withCount(['steps', 'issues'])
                    ->inGroup($request->input('story'), $request->input('epic'))
                    ->search($request->input('search'))
                    ->filterByQueryString()
                    ->orderBy('number')
                    ->paginate(50)
                    ->withQueryString()
            ),
            'openedTestCase' => $testCase ? new TestCaseResource($this->loadDetails($testCase)) : null,
            'statuses' => TestCaseStatus::options(),
        ]);
    }

    public function store(SaveTestCaseRequest $request, Project $project, SaveTestCase $saveTestCase): RedirectResponse
    {
        $this->authorize('create', [TestCase::class, $project]);

        $testCase = $saveTestCase->create($project, $request->user(), $request->validated());

        return redirect()->back()->success('Test case added', "TC-{$testCase->number} was successfully added.");
    }

    public function update(SaveTestCaseRequest $request, Project $project, TestCase $testCase, SaveTestCase $saveTestCase): RedirectResponse
    {
        $this->authorize('update', [$testCase, $project]);

        $saveTestCase->update($testCase, $request->user(), $request->validated());

        return redirect()->back()->success('Test case saved', "TC-{$testCase->number} was successfully saved.");
    }

    public function destroy(Project $project, TestCase $testCase): RedirectResponse
    {
        $this->authorize('delete', [$testCase, $project]);

        $testCase->delete();

        return redirect()
            ->route('projects.test-cases', $project)
            ->success('Test case deleted', "TC-{$testCase->number} was deleted.");
    }

    private function loadDetails(TestCase $testCase): TestCase
    {
        return $testCase->load([
            'story.epic:id,title',
            'steps',
            'attachments',
            'issues:id,project_id,test_case_id,number,title,status',
            'createdByUser:id,name,avatar',
            'executedByUser:id,name,avatar',
        ]);
    }
}
