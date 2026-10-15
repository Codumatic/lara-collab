<?php

namespace App\Http\Resources\Issue;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueResource extends JsonResource
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
            'project' => $this->whenLoaded('project', fn () => $this->project?->only(['id', 'name'])),
            'number' => $this->number,
            'title' => $this->title,
            'description' => $this->description,
            'steps_to_reproduce' => $this->steps_to_reproduce,
            'expected_result' => $this->expected_result,
            'actual_result' => $this->actual_result,
            'status' => $this->status?->value,
            'severity' => $this->severity?->value,
            'priority' => $this->priority?->value,
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'assigned_to_user' => $this->whenLoaded('assignedToUser', fn () => $this->assignedToUser?->only(['id', 'name', 'avatar'])),
            'reported_by_user' => $this->whenLoaded('reportedByUser', fn () => $this->reportedByUser?->only(['id', 'name', 'avatar'])),
            'labels' => $this->whenLoaded('labels', fn () => $this->labels->map->only(['id', 'name', 'color'])->values()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'archived_at' => $this->archived_at,
        ];
    }
}
