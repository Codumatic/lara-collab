<?php

namespace App\Http\Requests\Issue;

use App\Enums\IssuePriority;
use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Models\Project;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'steps_to_reproduce' => ['nullable', 'string'],
            'expected_result' => ['nullable', 'string'],
            'actual_result' => ['nullable', 'string'],
            'status' => ['nullable', Rule::enum(IssueStatus::class)],
            'severity' => ['nullable', Rule::enum(IssueSeverity::class)],
            'priority' => ['nullable', Rule::enum(IssuePriority::class)],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id', $this->assigneeHasProjectAccess()],
            'labels' => ['array'],
            'labels.*' => ['integer', 'exists:labels,id'],
            'test_case_id' => [
                'nullable',
                'integer',
                Rule::exists('test_cases', 'id')->where('project_id', $this->route('project')?->id),
            ],
            'attachments' => ['array'],
            'attachments.*' => ['file', 'max:20480'],
        ];
    }

    /**
     * Issues can only be assigned to team members who can see the project, never to clients.
     */
    protected function assigneeHasProjectAccess(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $assignee = User::find($value);
            $project = $this->route('project');

            if (! $assignee || ! $project instanceof Project) {
                return;
            }

            if ($assignee->hasRole('client')) {
                $fail('Issues can only be assigned to team members.');
            } elseif (! $assignee->hasProjectAccess($project)) {
                $fail('The selected assignee does not have access to this project.');
            }
        };
    }
}
