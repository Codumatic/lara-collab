<?php

namespace App\Http\Controllers\TestCase;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestCaseCommentController extends Controller
{
    public function index(Project $project, TestCase $testCase): JsonResponse
    {
        $this->authorize('view', [$testCase, $project]);

        return response()->json(
            $testCase->comments()->with('user:id,name,avatar,job_title')->latest()->get()
        );
    }

    public function store(Request $request, Project $project, TestCase $testCase): JsonResponse
    {
        $this->authorize('comment', [$testCase, $project]);

        $validated = $request->validate([
            'content' => ['required', 'string', 'min:8'],
        ]);

        $comment = $testCase->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        return response()->json(['comment' => $comment->load('user:id,name,avatar,job_title')]);
    }
}
