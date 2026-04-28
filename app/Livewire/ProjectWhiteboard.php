<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Whiteboard;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ProjectWhiteboard extends Component
{
    use WithFileUploads;

    public Project $project;

    public Whiteboard $whiteboard;

    #[Validate('nullable|image|max:10240')]
    public $pendingImage = null;

    public function mount(Project $project): void
    {
        abort_unless(Auth::user()->can('view', $project), 404);

        $this->project = $project;

        $this->whiteboard = Whiteboard::firstOrCreate(
            ['project_id' => $project->id],
            ['updated_by' => Auth::id()],
        );
    }

    public function save(array $data): void
    {
        $this->whiteboard->forceFill([
            'data' => $data,
            'updated_by' => Auth::id(),
        ])->save();
    }

    /**
     * Persist the most recently uploaded image to the project's whiteboard media collection
     * and return its public URL. The JS layer calls this after `$wire.upload('pendingImage', file)`
     * resolves, then inserts the URL into the Excalidraw scene.
     *
     * @return array{url: string, mediaId: int}|null
     */
    public function commitImage(): ?array
    {
        $this->validate();

        if (! $this->pendingImage instanceof TemporaryUploadedFile) {
            return null;
        }

        $media = $this->project->addMedia($this->pendingImage->getRealPath())
            ->usingName($this->pendingImage->getClientOriginalName())
            ->usingFileName($this->pendingImage->getClientOriginalName())
            ->toMediaCollection('whiteboard');

        $this->pendingImage = null;

        return [
            'url' => $media->getUrl(),
            'mediaId' => $media->id,
        ];
    }

    public function render()
    {
        return view('livewire.project-whiteboard');
    }
}
