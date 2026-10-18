<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\TestCase;
use App\Models\User;

/**
 * Covers test cases and the epics and stories that group them.
 */
class TestCasePolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('view test cases') && $user->hasProjectAccess($project);
    }

    public function view(User $user, TestCase $testCase, Project $project): bool
    {
        return $this->viewAny($user, $project) && $testCase->project_id === $project->id;
    }

    public function create(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('create test case') && $user->hasProjectAccess($project);
    }

    public function update(User $user, TestCase $testCase, Project $project): bool
    {
        return $user->hasPermissionTo('edit test case')
            && $user->hasProjectAccess($project)
            && $testCase->project_id === $project->id;
    }

    public function delete(User $user, TestCase $testCase, Project $project): bool
    {
        return $user->hasPermissionTo('delete test case')
            && $user->hasProjectAccess($project)
            && $testCase->project_id === $project->id;
    }

    /**
     * Adding, renaming and deleting the epics and stories of a project.
     */
    public function manageStructure(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('edit test case') && $user->hasProjectAccess($project);
    }

    /**
     * Anyone who can see a test case can discuss it.
     */
    public function comment(User $user, TestCase $testCase, Project $project): bool
    {
        return $this->view($user, $testCase, $project);
    }
}
