<?php

namespace App\Filament\App\Resources\TaskResource\Pages;

use App\Filament\App\Resources\TaskResource;
use App\Models\Project;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $project = Project::findOrFail($data['project_id']);
        DB::transaction(function () use ($project) {
            $project->increment('task_counter');
        });
        $project->refresh();

        $data['key'] = $project->key.'-'.$project->task_counter;
        $data['created_by'] = Auth::id();

        return $data;
    }
}
