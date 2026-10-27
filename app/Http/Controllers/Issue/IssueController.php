<?php

namespace App\Http\Controllers\Issue;

use App\Actions\Issue\CreateIssue;
use App\Actions\Issue\UpdateIssue;
use App\Enums\IssueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Issue\StoreIssueRequest;
use App\Http\Requests\Issue\UpdateIssueRequest;
use App\Http\Resources\Issue\IssueResource;
use App\Models\Issue;
use App\Models\Label;
use App\Models\Project;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class IssueController extends Controller
{
    /**
     * List the project's issues. When an issue is given, it opens in the drawer.
     */
    public function index(Request $request, Project $project, ?Issue $issue = null): Response
    {
        $this->authorize('viewAny', [Issue::class, $project]);

        if ($issue) {
            $this->authorize('view', [$issue, $project]);
        }

        $relations = [
            'assignedToUser:id,name,avatar',
            'reportedByUser:id,name,avatar',
            'labels:id,name,color',
            'attachments',
            'testCase:id,number,title,status',
        ];
        $isBoardView = $request->input('view') === 'board' && ! $request->has('archived');
        $usersWithAccess = PermissionService::usersWithAccessToProject($project);

        $listQuery = fn () => $project->issues()
            ->with($relations)
            ->search($request->input('search'))
            ->filterByQueryString();

        return Inertia::render('Projects/Issues/Index', [
            'project' => $project,
            'items' => $isBoardView ? null : IssueResource::collection(
                $listQuery()
                    ->when($request->has('archived'), fn ($query) => $query->onlyArchived())
                    ->sortedBy($request->input('sort'))
                    ->paginate(25)
                    ->withQueryString()
            ),
            'boardIssues' => $isBoardView
                ? IssueResource::collection($listQuery()->sortedBy($request->input('sort'))->get())
                : null,
            'openedIssue' => $issue ? new IssueResource($issue->load($relations)) : null,
            'usersWithAccessToProject' => $usersWithAccess,
            'assignableUsers' => $usersWithAccess
                ->reject(fn (array $user) => collect($user['roles'] ?? [])->contains('name', 'client'))
                ->values(),
            'labels' => Label::orderBy('name')->get(['id', 'name', 'color']),
            'dropdowns' => Issue::dropdownOptions(),
            'testCaseOptions' => $project->testCases()
                ->orderBy('number')
                ->get(['id', 'number', 'title'])
                ->map(fn ($testCase) => ['value' => (string) $testCase->id, 'label' => "TC-{$testCase->number}: {$testCase->title}"]),
        ]);
    }

    public function store(StoreIssueRequest $request, Project $project, CreateIssue $createIssue): RedirectResponse
    {
        $this->authorize('create', [Issue::class, $project]);

        $issue = $createIssue->create($project, $request->user(), $request->validated());

        return redirect()
            ->back()
            ->success('Issue added', "Issue ISS-{$issue->number} was successfully added.");
    }

    public function update(UpdateIssueRequest $request, Project $project, Issue $issue, UpdateIssue $updateIssue): RedirectResponse
    {
        $this->authorize('update', [$issue, $project]);

        $updateIssue->update($issue, $request->user(), $request->validated());

        return redirect()->back()->success('Issue saved', "Issue ISS-{$issue->number} was successfully saved.");
    }

    /**
     * Change only the status, used when a card is dragged between board columns.
     */
    public function updateStatus(Request $request, Project $project, Issue $issue): JsonResponse
    {
        $this->authorize('update', [$issue, $project]);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(IssueStatus::class)],
        ]);

        $issue->update(['status' => $validated['status']]);

        return response()->json(['status' => $issue->status->value]);
    }

    public function destroy(Project $project, Issue $issue): RedirectResponse
    {
        $this->authorize('delete', [$issue, $project]);

        $issue->archive();

        return redirect()
            ->route('projects.issues', $project)
            ->success('Issue archived', "Issue ISS-{$issue->number} was successfully archived.");
    }

    public function restore(Project $project, Issue $issue): RedirectResponse
    {
        $this->authorize('restore', [$issue, $project]);

        $issue->unArchive();

        return redirect()->back()->success('Issue restored', "Issue ISS-{$issue->number} was successfully restored.");
    }
}
