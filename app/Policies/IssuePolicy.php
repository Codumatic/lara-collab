<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;

class IssuePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('view issues') && $user->hasProjectAccess($project);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Issue $issue, Project $project): bool
    {
        return $this->viewAny($user, $project) && $issue->project_id === $project->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('create issue') && $user->hasProjectAccess($project);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Issue $issue, Project $project): bool
    {
        return $user->hasPermissionTo('edit issue')
            && $user->hasProjectAccess($project)
            && $issue->project_id === $project->id;
    }

    /**
     * Determine whether the user can archive the model.
     */
    public function delete(User $user, Issue $issue, Project $project): bool
    {
        return $user->hasPermissionTo('archive issue')
            && $user->hasProjectAccess($project)
            && $issue->project_id === $project->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Issue $issue, Project $project): bool
    {
        return $user->hasPermissionTo('restore issue')
            && $user->hasProjectAccess($project)
            && $issue->project_id === $project->id;
    }
}
