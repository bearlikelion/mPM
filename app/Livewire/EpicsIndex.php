<?php

namespace App\Livewire;

use App\Models\Epic;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class EpicsIndex extends Component
{
    #[Url(as: 'project')]
    public ?int $projectId = null;

    protected function availableProjects()
    {
        $orgIds = Auth::user()->organizations()->pluck('organizations.id');

        return Project::whereIn('organization_id', $orgIds);
    }

    public function render()
    {
        $projects = $this->availableProjects()->orderBy('name')->get();

        $epics = Epic::with('project')
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'done')])
            ->whereIn('project_id', $this->projectId ? [$this->projectId] : $projects->pluck('id'))
            ->orderByDesc('id')
            ->get();

        return view('livewire.epics-index', [
            'projects' => $projects,
            'epics' => $epics,
        ]);
    }
}
