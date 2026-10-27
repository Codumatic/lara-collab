<?php

namespace App\Http\Controllers\Issue;

use App\Http\Controllers\Controller;
use App\Http\Requests\Issue\StoreIssueCommentRequest;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class IssueCommentController extends Controller
{
    public function index(Project $project, Issue $issue): JsonResponse
    {
        $this->authorize('view', [$issue, $project]);

        return response()->json(
            $issue->comments()->with('user:id,name,avatar,job_title')->latest()->get()
        );
    }

    public function store(StoreIssueCommentRequest $request, Project $project, Issue $issue): JsonResponse
    {
        $this->authorize('comment', [$issue, $project]);

        $comment = $issue->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->validated('content'),
        ]);

        return response()->json(['comment' => $comment->load('user:id,name,avatar,job_title')]);
    }
}
