<?php

namespace App\Notifications;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class IssueAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Issue $issue, public User $assignedBy) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'issue_id' => $this->issue->id,
            'title' => "{$this->assignedBy->name} assigned you ISS-{$this->issue->number}",
            'subtitle' => "\"{$this->issue->title}\" on \"{$this->issue->project->name}\" project",
            'link' => route('projects.issues.open', [$this->issue->project_id, $this->issue->id]),
            'created_at' => $notifiable->created_at,
            'read_at' => $notifiable->read_at,
        ];
    }
}
