<?php

use App\Filament\Admin\Resources\ProjectResource\Pages\ListProjects;
use App\Filament\Admin\Resources\ProjectResource\Pages\ViewProject;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
    filament()->setCurrentPanel('admin');
});

it('lists projects across organizations', function () {
    $admin = User::factory()->create();
    $admin->assignRole('site_admin');

    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $projectA = Project::factory()->create(['organization_id' => $orgA->id]);
    $projectB = Project::factory()->create(['organization_id' => $orgB->id]);

    $this->actingAs($admin);

    Livewire::test(ListProjects::class)
        ->assertCanSeeTableRecords([$projectA, $projectB]);
});

it('renders the project detail page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('site_admin');

    $org = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $org->id]);
    Task::factory()->create(['project_id' => $project->id, 'status' => 'todo']);
    Task::factory()->create(['project_id' => $project->id, 'status' => 'done']);

    $this->actingAs($admin);

    Livewire::test(ViewProject::class, ['record' => $project->id])
        ->assertSeeText('Task status distribution')
        ->assertSeeText('Priority distribution')
        ->assertSeeText('Epics')
        ->assertSeeText('Sprints')
        ->assertSeeText('Members');
});
