<?php

namespace App\Http\Controllers\Issue;

use App\Actions\UploadAttachments;
use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\IssueAttachment;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class IssueAttachmentController extends Controller
{
    public function store(Request $request, Project $project, Issue $issue, UploadAttachments $uploadAttachments): JsonResponse
    {
        $this->authorize('update', [$issue, $project]);

        $validated = $request->validate([
            'attachments' => ['required', 'array'],
            'attachments.*' => ['file', 'max:20480'],
        ]);

        $files = $uploadAttachments->upload($issue->attachments(), "issues/{$issue->id}", $request->user(), $validated['attachments']);

        return response()->json(['files' => $files]);
    }

    public function destroy(Project $project, Issue $issue, IssueAttachment $attachment): JsonResponse
    {
        $this->authorize('update', [$issue, $project]);

        abort_unless($attachment->issue_id === $issue->id, 404);

        File::delete(public_path($attachment->path));

        if ($attachment->thumb) {
            File::delete(public_path($attachment->thumb));
        }

        $attachment->delete();

        return response()->json();
    }
}
