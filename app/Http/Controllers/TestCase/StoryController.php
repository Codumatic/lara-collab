<?php

namespace App\Http\Controllers\TestCase;

use App\Http\Controllers\Controller;
use App\Models\Epic;
use App\Models\Project;
use App\Models\Story;
use App\Models\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    public function store(Request $request, Project $project, Epic $epic): RedirectResponse
    {
        $this->authorize('manageStructure', [TestCase::class, $project]);

        $epic->stories()->create($this->validated($request));

        return redirect()->back()->success('Story added', 'The story was successfully added.');
    }

    public function update(Request $request, Project $project, Epic $epic, Story $story): RedirectResponse
    {
        $this->authorize('manageStructure', [TestCase::class, $project]);

        $story->update($this->validated($request));

        return redirect()->back()->success('Story saved', 'The story was successfully saved.');
    }

    /**
     * The story's test cases are kept and move to "Without story".
     */
    public function destroy(Project $project, Epic $epic, Story $story): RedirectResponse
    {
        $this->authorize('manageStructure', [TestCase::class, $project]);

        $story->delete();

        return redirect()
            ->route('projects.test-cases', $project)
            ->success('Story deleted', 'Its test cases were moved to "Without story".');
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
