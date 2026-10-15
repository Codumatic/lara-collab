<?php

namespace App\Http\Controllers\Issue;

use App\Http\Controllers\Controller;
use App\Http\Resources\Issue\IssueResource;
use App\Models\Issue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AllIssuesController extends Controller
{
    /**
     * List the issues of every project the user can access.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAcrossProjects', Issue::class);

        return Inertia::render('Issues/Index', [
            'items' => IssueResource::collection(
                Issue::visibleTo($request->user())
                    ->with(['project:id,name', 'assignedToUser:id,name,avatar', 'reportedByUser:id,name,avatar'])
                    ->latest()
                    ->paginate(25)
                    ->withQueryString()
            ),
            'dropdowns' => Issue::dropdownOptions(),
        ]);
    }
}
