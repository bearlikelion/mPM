<?php

namespace App\Livewire;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TaskDetail extends Component
{
    public Task $task;

    public string $newComment = '';

    public string $status;

    public string $priority;

    public ?int $storyPoints = null;

    public function mount(string $key): void
    {
        $orgIds = Auth::user()->organizations()->pluck('organizations.id');
        $projectIds = Project::whereIn('organization_id', $orgIds)->pluck('id');

        $this->task = Task::with('project', 'epic', 'sprint', 'assignees', 'tags', 'comments.user', 'creator')
            ->whereIn('project_id', $projectIds)
            ->where('key', $key)
            ->firstOrFail();

        $this->status = $this->task->status;
        $this->priority = $this->task->priority;
        $this->storyPoints = $this->task->story_points;
    }

    public function updateField(string $field): void
    {
        $allowed = [
            'status' => Task::STATUSES,
            'priority' => Task::PRIORITIES,
            'storyPoints' => array_map('strval', Task::STORY_POINTS),
        ];

        if (! array_key_exists($field, $allowed)) {
            return;
        }

        $value = $this->{$field};

        if ($field === 'storyPoints') {
            $this->task->update(['story_points' => $value ?: null]);
        } else {
            $this->task->update([$field => $value]);
        }
    }

    public function addComment(): void
    {
        $this->validate(['newComment' => 'required|string|min:1']);

        Comment::create([
            'task_id' => $this->task->id,
            'user_id' => Auth::id(),
            'body' => $this->newComment,
        ]);

        $this->newComment = '';
        $this->task->load('comments.user');
    }

    public function render()
    {
        return view('livewire.task-detail');
    }
}
