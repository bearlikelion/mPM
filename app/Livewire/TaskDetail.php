<?php

namespace App\Livewire;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class TaskDetail extends Component
{
    use WithFileUploads;

    public Task $task;

    public bool $embedded = false;

    public string $newComment = '';

    public array $attachments = [];

    public string $status;

    public string $priority;

    public ?int $storyPoints = null;

    public function mount(string $taskKey, bool $embedded = false): void
    {
        $this->embedded = $embedded;

        $orgIds = Auth::user()->organizations()->pluck('organizations.id');
        $projectIds = Project::whereIn('organization_id', $orgIds)->pluck('id');

        $this->task = Task::with('project', 'epic', 'sprint', 'assignees', 'tags', 'comments.user', 'comments.media', 'creator', 'media')
            ->whereIn('project_id', $projectIds)
            ->where('key', $taskKey)
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
        $this->validate([
            'newComment' => 'nullable|string',
            'attachments.*' => 'file|max:10240',
        ]);

        if (trim($this->newComment) === '' && empty($this->attachments)) {
            $this->addError('newComment', 'Write something or attach a file.');

            return;
        }

        $comment = Comment::create([
            'task_id' => $this->task->id,
            'user_id' => Auth::id(),
            'body' => $this->newComment ?: '',
        ]);

        foreach ($this->attachments as $file) {
            $comment->addMedia($file->getRealPath())
                ->usingName($file->getClientOriginalName())
                ->usingFileName($file->getClientOriginalName())
                ->toMediaCollection('attachments');
        }

        $this->newComment = '';
        $this->attachments = [];
        $this->task->load('comments.user', 'comments.media');
    }

    public function render()
    {
        return view('livewire.task-detail');
    }
}
