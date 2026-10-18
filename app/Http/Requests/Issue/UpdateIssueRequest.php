<?php

namespace App\Http\Requests\Issue;

use App\Enums\IssueStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UpdateIssueRequest extends StoreIssueRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...Arr::except(parent::rules(), ['attachments', 'attachments.*']),
            'status' => ['required', Rule::enum(IssueStatus::class)],
        ];
    }
}
