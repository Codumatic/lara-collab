<?php

use App\Enums\Severity;
use App\Enums\TaskStatus;
use App\Models\ClientCompany;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ProductionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TaskPrioritySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CountrySeeder::class);
    $this->seed(CurrencySeeder::class);
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(TaskPrioritySeeder::class);

    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->user->assignRole('admin');

    $clientCompany = ClientCompany::factory()->create();

    $this->project = Project::create([
        'client_company_id' => $clientCompany->id,
        'name' => 'Test Project',
        'hourly_rate' => 5000,
        'default_pricing_type' => 'hourly',
    ]);

    $this->taskGroup = TaskGroup::create([
        'project_id' => $this->project->id,
        'name' => 'To Do',
        'color' => 'blue',
    ]);

    $this->validTaskPayload = fn (array $overrides = []) => array_merge([
        'name' => 'Login button does nothing',
        'group_id' => $this->taskGroup->id,
        'assigned_to_user_id' => null,
        'description' => 'Description',
        'due_on' => null,
        'estimation' => null,
        'priority_id' => null,
        'pricing_type' => 'hourly',
        'fixed_price' => null,
        'hidden_from_clients' => false,
        'billable' => true,
        'subscribed_users' => [],
        'labels' => [],
        'attachments' => [],
    ], $overrides);
});

function createIssueTask(Project $project, TaskGroup $taskGroup, User $user, array $attributes = []): Task
{
    return Task::create(array_merge([
        'project_id' => $project->id,
        'group_id' => $taskGroup->id,
        'created_by_user_id' => $user->id,
        'name' => 'Test Task',
        'number' => 1,
        'pricing_type' => 'hourly',
        'hidden_from_clients' => false,
        'billable' => true,
    ], $attributes));
}

it('stores issue fields when creating a task', function () {
    $this->post(route('projects.tasks.store', $this->project), ($this->validTaskPayload)([
        'steps_to_reproduce' => '<ol><li>Open login page</li><li>Click login</li></ol>',
        'expected_result' => 'User is logged in',
        'actual_result' => 'Nothing happens',
        'severity' => 'critical',
        'case_link' => 'https://example.com/cases/42',
    ]))->assertRedirect()->assertSessionHasNoErrors();

    $task = Task::firstWhere('name', 'Login button does nothing');

    expect($task->steps_to_reproduce)->toBe('<ol><li>Open login page</li><li>Click login</li></ol>')
        ->and($task->expected_result)->toBe('User is logged in')
        ->and($task->actual_result)->toBe('Nothing happens')
        ->and($task->severity)->toBe(Severity::CRITICAL)
        ->and($task->case_link)->toBe('https://example.com/cases/42');
});

it('creates a task without issue fields', function () {
    $this->post(route('projects.tasks.store', $this->project), ($this->validTaskPayload)())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $task = Task::firstWhere('name', 'Login button does nothing');

    expect($task->steps_to_reproduce)->toBeNull()
        ->and($task->severity)->toBeNull()
        ->and($task->case_link)->toBeNull();
});

it('rejects an invalid severity, status and case link', function () {
    $this->post(route('projects.tasks.store', $this->project), ($this->validTaskPayload)([
        'severity' => 'catastrophic',
        'status' => 'archived',
        'case_link' => 'not a url',
    ]))->assertSessionHasErrors(['severity', 'status', 'case_link']);
});

it('defaults the status to new and keeps it independent from the task group', function () {
    $this->post(route('projects.tasks.store', $this->project), ($this->validTaskPayload)())
        ->assertSessionHasNoErrors();

    $task = Task::firstWhere('name', 'Login button does nothing');

    expect($task->status)->toBe(TaskStatus::NEW)
        ->and($task->group_id)->toBe($this->taskGroup->id);
});

it('stores the selected status when creating a task', function () {
    $this->post(route('projects.tasks.store', $this->project), ($this->validTaskPayload)([
        'status' => 'in_progress',
    ]))->assertSessionHasNoErrors();

    expect(Task::firstWhere('name', 'Login button does nothing')->status)->toBe(TaskStatus::IN_PROGRESS);
});

it('updates issue fields one at a time', function (string $field, string $value) {
    $task = createIssueTask($this->project, $this->taskGroup, $this->user);

    $this->put(route('projects.tasks.update', [$this->project, $task]), [$field => $value])
        ->assertOk();

    expect($task->refresh()->getRawOriginal($field))->toBe($value);
})->with([
    ['steps_to_reproduce', '<p>Step one</p>'],
    ['expected_result', 'It works'],
    ['actual_result', 'It breaks'],
    ['severity', 'medium'],
    ['status', 'resolved'],
    ['case_link', 'https://example.com/cases/7'],
]);

it('creates a task without a task group', function () {
    $payload = ($this->validTaskPayload)(['status' => 'resolved']);
    unset($payload['group_id']);

    $this->post(route('projects.tasks.store', $this->project), $payload)
        ->assertSessionHasNoErrors();

    $task = Task::firstWhere('name', 'Login button does nothing');

    expect($task->group_id)->toBeNull()
        ->and($task->status)->toBe(TaskStatus::RESOLVED);
});

it('shows one board column per status with the tasks grouped by status', function () {
    $this->seed(ProductionSeeder::class);

    $newTask = createIssueTask($this->project, $this->taskGroup, $this->user, ['status' => 'new']);
    $closedTask = createIssueTask($this->project, $this->taskGroup, $this->user, ['status' => 'closed', 'number' => 2]);

    $this->get(route('projects.tasks', $this->project))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('taskGroups', TaskStatus::columns())
            ->where('groupedTasks.new.0.id', $newTask->id)
            ->where('groupedTasks.closed.0.id', $closedTask->id)
            ->has('groupedTasks.in_progress', 0)
        );
});

it('changes the status when a task is dragged to another column', function () {
    $task = createIssueTask($this->project, $this->taskGroup, $this->user, ['status' => 'new']);

    $this->post(route('projects.tasks.move', $this->project), [
        'ids' => [$task->id],
        'from_status' => 'new',
        'to_status' => 'deployed',
        'from_index' => 0,
        'to_index' => 0,
    ])->assertOk();

    expect($task->refresh()->status)->toBe(TaskStatus::DEPLOYED)
        ->and($task->group_id)->toBe($this->taskGroup->id);
});

it('rejects moving a task to an unknown status', function () {
    $task = createIssueTask($this->project, $this->taskGroup, $this->user, ['status' => 'new']);

    $this->postJson(route('projects.tasks.move', $this->project), [
        'ids' => [$task->id],
        'from_status' => 'new',
        'to_status' => 'archived',
        'from_index' => 0,
        'to_index' => 0,
    ])->assertJsonPath('message', 'The selected to status is invalid.');

    expect($task->refresh()->status)->toBe(TaskStatus::NEW);
});

it('clears severity when updated to null', function () {
    $task = createIssueTask($this->project, $this->taskGroup, $this->user, ['severity' => 'major']);

    $this->put(route('projects.tasks.update', [$this->project, $task]), ['severity' => null])
        ->assertOk();

    expect($task->refresh()->severity)->toBeNull();
});
