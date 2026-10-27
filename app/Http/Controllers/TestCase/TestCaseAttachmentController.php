<?php

namespace App\Http\Controllers\TestCase;

use App\Actions\UploadAttachments;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TestCase;
use App\Models\TestCaseAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TestCaseAttachmentController extends Controller
{
    public function store(Request $request, Project $project, TestCase $testCase, UploadAttachments $uploadAttachments): JsonResponse
    {
        $this->authorize('update', [$testCase, $project]);

        $validated = $request->validate([
            'attachments' => ['required', 'array'],
            'attachments.*' => ['file', 'max:20480'],
        ]);

        $files = $uploadAttachments->upload(
            $testCase->attachments(),
            "test-cases/{$testCase->id}",
            $request->user(),
            $validated['attachments']
        );

        return response()->json(['files' => $files]);
    }

    public function destroy(Project $project, TestCase $testCase, TestCaseAttachment $attachment): JsonResponse
    {
        $this->authorize('update', [$testCase, $project]);

        abort_unless($attachment->test_case_id === $testCase->id, 404);

        File::delete(public_path($attachment->path));

        if ($attachment->thumb) {
            File::delete(public_path($attachment->thumb));
        }

        $attachment->delete();

        return response()->json();
    }
}
