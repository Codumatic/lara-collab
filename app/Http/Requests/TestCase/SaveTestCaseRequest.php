<?php

namespace App\Http\Requests\TestCase;

use App\Enums\TestCaseStatus;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTestCaseRequest extends FormRequest
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
        /** @var Project $project */
        $project = $this->route('project');

        return [
            'title' => ['required', 'string', 'max:255'],
            'story_id' => [
                'nullable',
                'integer',
                Rule::exists('stories', 'id')->whereIn('epic_id', $project->epics()->select('id')),
            ],
            'description' => ['nullable', 'string'],
            'preconditions' => ['nullable', 'string'],
            'test_data' => ['nullable', 'string'],
            'environment' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(TestCaseStatus::class)],
            'executed_at' => ['nullable', 'date'],
            'steps' => ['array'],
            'steps.*.action' => ['required', 'string'],
            'steps.*.expected_result' => ['nullable', 'string'],
            'attachments' => ['array'],
            'attachments.*' => ['file', 'max:20480'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'steps.*.action.required' => 'Every step needs a description.',
        ];
    }
}
