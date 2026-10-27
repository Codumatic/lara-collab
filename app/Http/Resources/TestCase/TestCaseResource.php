<?php

namespace App\Http\Resources\TestCase;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestCaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'story_id' => $this->story_id,
            'number' => $this->number,
            'title' => $this->title,
            'description' => $this->description,
            'preconditions' => $this->preconditions,
            'test_data' => $this->test_data,
            'environment' => $this->environment,
            'status' => $this->status?->value,
            'executed_at' => $this->executed_at,
            'story' => $this->whenLoaded('story', fn () => $this->story ? [
                'id' => $this->story->id,
                'title' => $this->story->title,
                'epic' => $this->story->relationLoaded('epic') ? $this->story->epic?->only(['id', 'title']) : null,
            ] : null),
            'created_by_user' => $this->whenLoaded('createdByUser', fn () => $this->createdByUser?->only(['id', 'name', 'avatar'])),
            'executed_by_user' => $this->whenLoaded('executedByUser', fn () => $this->executedByUser?->only(['id', 'name', 'avatar'])),
            'steps' => $this->whenLoaded('steps', fn () => $this->steps->map->only(['id', 'position', 'action', 'expected_result'])->values()),
            'steps_count' => $this->whenCounted('steps'),
            'issues_count' => $this->whenCounted('issues'),
            'issues' => $this->whenLoaded('issues', fn () => $this->issues->map->only(['id', 'project_id', 'number', 'title', 'status'])->values()),
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments->values()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
