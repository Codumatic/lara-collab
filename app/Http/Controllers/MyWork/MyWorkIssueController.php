<?php

namespace App\Http\Controllers\MyWork;

use App\Enums\IssueStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Issue\IssueResource;
use App\Models\Issue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyWorkIssueController extends Controller
{
    /**
     * List the open issues assigned to the current user, across the projects they can access.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAcrossProjects', Issue::class);

        return Inertia::render('MyWork/Issues/Index', [
            'items' => IssueResource::collection(
                Issue::visibleTo($request->user())
                    ->where('assigned_to_user_id', $request->user()->id)
                    ->where('status', '!=', IssueStatus::CLOSED)
                    ->with(['project:id,name', 'assignedToUser:id,name,avatar', 'reportedByUser:id,name,avatar'])
                    ->latest()
                    ->paginate(25)
                    ->withQueryString()
            ),
            'dropdowns' => Issue::dropdownOptions(),
        ]);
    }
}
