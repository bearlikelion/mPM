<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TaskActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $kind,
        public Task $task,
        public string $title,
        public string $body,
        public ?User $actor = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'task_id' => $this->task->id,
            'task_key' => $this->task->key,
            'task_title' => $this->task->title,
            'project_id' => $this->task->project_id,
            'title' => $this->title,
            'body' => $this->body,
            'url' => route('tasks.show', $this->task->key),
            'actor_id' => $this->actor?->id,
            'actor_name' => $this->actor?->name,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
