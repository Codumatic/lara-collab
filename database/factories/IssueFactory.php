<?php

namespace Database\Factories;

use App\Enums\IssuePriority;
use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Models\ClientCompany;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => fn () => Project::withoutEvents(fn () => Project::create([
                'client_company_id' => ClientCompany::factory()->create()->id,
                'name' => fake()->words(2, true),
            ]))->id,
            'reported_by_user_id' => User::factory(),
            'assigned_to_user_id' => null,
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'steps_to_reproduce' => '<ol><li>'.fake()->sentence().'</li><li>'.fake()->sentence().'</li></ol>',
            'expected_result' => fake()->sentence(),
            'actual_result' => fake()->sentence(),
            'status' => IssueStatus::NEW,
            'severity' => fake()->randomElement(IssueSeverity::cases()),
            'priority' => fake()->randomElement(IssuePriority::cases()),
        ];
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn (): array => ['project_id' => $project->id]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (): array => ['assigned_to_user_id' => $user->id]);
    }

    public function withStatus(IssueStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
