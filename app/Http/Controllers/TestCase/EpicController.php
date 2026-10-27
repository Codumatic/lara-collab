<?php

namespace App\Http\Controllers\TestCase;

use App\Http\Controllers\Controller;
use App\Models\Epic;
use App\Models\Project;
use App\Models\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EpicController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('manageStructure', [TestCase::class, $project]);

        $project->epics()->create($this->validated($request));

        return redirect()->back()->success('Epic added', 'The epic was successfully added.');
    }

    public function update(Request $request, Project $project, Epic $epic): RedirectResponse
    {
        $this->authorize('manageStructure', [TestCase::class, $project]);

        $epic->update($this->validated($request));

        return redirect()->back()->success('Epic saved', 'The epic was successfully saved.');
    }

    /**
     * Deleting an epic deletes its stories; their test cases are kept and move to "Without story".
     */
    public function destroy(Project $project, Epic $epic): RedirectResponse
    {
        $this->authorize('manageStructure', [TestCase::class, $project]);

        $epic->delete();

        return redirect()
            ->route('projects.test-cases', $project)
            ->success('Epic deleted', 'Its test cases were moved to "Without story".');
    }

    /**
     * @return array{title: string, description?: ?string}
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
