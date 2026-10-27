<?php

namespace App\Http\Controllers\MyWork;

use App\Http\Controllers\Controller;
use App\Http\Resources\Issue\IssueResource;
use App\Models\Issue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyWorkIssueController extends Controller
{
    /**
     * List the issues assigned to the current user, across the projects they can access.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAcrossProjects', Issue::class);

        return Inertia::render('MyWork/Issues/Index', [
            'items' => IssueResource::collection(
                Issue::visibleTo($request->user())
                    ->where('assigned_to_user_id', $request->user()->id)
                    ->with(['project:id,name', 'assignedToUser:id,name,avatar', 'reportedByUser:id,name,avatar', 'labels:id,name,color'])
                    ->search($request->input('search'))
                    ->filterByQueryString()
                    ->sortedBy($request->input('sort'), 'created_at')
                    ->paginate(25)
                    ->withQueryString()
            ),
            'dropdowns' => Issue::dropdownOptions(),
            'filterOptions' => Issue::filterOptionsFor(
                Issue::visibleTo($request->user())->where('assigned_to_user_id', $request->user()->id)
            ),
        ]);
    }
}
