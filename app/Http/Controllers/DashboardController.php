<?php

namespace App\Http\Controllers;

use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Task;
use App\Services\PermissionService;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $projectIds = PermissionService::projectsThatUserCanAccess(auth()->user())->pluck('id');
        $canViewIssues = auth()->user()->hasPermissionTo('view issues');

        return Inertia::render('Dashboard/Index', [
            'projects' => Project::whereIn('id', $projectIds)
                ->with([
                    'clientCompany:id,name',
                ])
                ->withCount([
                    'tasks AS all_tasks_count',
                    'tasks AS completed_tasks_count' => fn ($query) => $query->whereNotNull('completed_at'),
                    'tasks AS overdue_tasks_count' => fn ($query) => $query->whereNull('completed_at')->whereDate('due_on', '<', now()),
                ])
                ->when($canViewIssues, fn ($query) => $query->withCount([
                    'issues AS open_issues_count' => fn ($query) => $query->where('status', '!=', IssueStatus::CLOSED),
                    'issues AS critical_open_issues_count' => fn ($query) => $query
                        ->where('status', '!=', IssueStatus::CLOSED)
                        ->where('severity', IssueSeverity::CRITICAL),
                ]))
                ->withExists('favoritedByAuthUser AS favorite')
                ->orderBy('favorite', 'desc')
                ->orderBy('name', 'asc')
                ->get(['id', 'name']),
            'overdueTasks' => Task::whereIn('project_id', $projectIds)
                ->whereNull('completed_at')
                ->whereDate('due_on', '<', now())
                ->where('assigned_to_user_id', auth()->id())
                ->with('project:id,name')
                ->with('taskGroup:id,name')
                ->orderBy('due_on')
                ->get(['id', 'name', 'due_on', 'group_id', 'project_id']),
            'recentlyAssignedTasks' => Task::whereIn('project_id', $projectIds)
                ->whereNull('completed_at')
                ->whereNotNull('assigned_at')
                ->where('assigned_to_user_id', auth()->id())
                ->with('project:id,name')
                ->with('taskGroup:id,name')
                ->orderBy('assigned_at')
                ->limit(10)
                ->get(['id', 'name', 'assigned_at', 'group_id', 'project_id']),
            'recentComments' => Comment::query()
                ->whereHas('task', function ($query) use ($projectIds) {
                    $query->whereIn('project_id', $projectIds)
                        ->where('assigned_to_user_id', auth()->id());
                })
                ->with([
                    'task:id,name,project_id',
                    'task.project:id,name',
                    'user:id,name',
                ])
                ->latest()
                ->get(),
            'issueStats' => $canViewIssues ? $this->issueStats($projectIds) : null,
            'assignedIssues' => $canViewIssues
                ? Issue::whereIn('project_id', $projectIds)
                    ->where('assigned_to_user_id', auth()->id())
                    ->where('status', '!=', IssueStatus::CLOSED)
                    ->with('project:id,name')
                    ->latest()
                    ->limit(10)
                    ->get(['id', 'number', 'title', 'status', 'severity', 'project_id', 'created_at'])
                : null,
        ]);
    }

    /**
     * Issue counts per status, and open issue counts per severity, across the given projects.
     *
     * @param  Collection<int, int>  $projectIds
     * @return array{byStatus: array<int, array{value: string, label: string, count: int}>, openBySeverity: array<int, array{value: string, label: string, count: int}>, total: int, open: int}
     */
    private function issueStats(Collection $projectIds): array
    {
        $countsByStatus = Issue::whereIn('project_id', $projectIds)
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $openCountsBySeverity = Issue::whereIn('project_id', $projectIds)
            ->where('status', '!=', IssueStatus::CLOSED)
            ->whereNotNull('severity')
            ->selectRaw('severity, COUNT(*) AS total')
            ->groupBy('severity')
            ->pluck('total', 'severity');

        $withCounts = fn (array $options, Collection $counts): array => array_map(
            fn (array $option): array => [...$option, 'count' => (int) ($counts[$option['value']] ?? 0)],
            $options,
        );

        return [
            'byStatus' => $withCounts(IssueStatus::options(), $countsByStatus),
            'openBySeverity' => $withCounts(IssueSeverity::options(), $openCountsBySeverity),
            'total' => (int) $countsByStatus->sum(),
            'open' => (int) $countsByStatus->except(IssueStatus::CLOSED->value)->sum(),
        ];
    }
}
