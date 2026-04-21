<?php

use App\Filament\Admin\Resources\UserResource\Pages\ListUsers;
use App\Filament\Admin\Resources\UserResource\Pages\ViewUser;
use App\Models\Comment;
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

it('renders user index with deep columns', function () {
    $admin = User::factory()->create();
    $admin->assignRole('site_admin');
    $other = User::factory()->create();
    $org = Organization::factory()->create();
    $org->users()->attach($other, ['role' => 'member', 'joined_at' => now()]);
    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$admin, $other])
        ->assertSeeText('Open tasks');
});

it('renders user detail page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('site_admin');
    $target = User::factory()->create();
    $org = Organization::factory()->create();
    $org->users()->attach($target, ['role' => 'member', 'joined_at' => now()]);
    $project = Project::factory()->create(['organization_id' => $org->id]);
    $task = Task::factory()->create(['project_id' => $project->id, 'status' => 'todo']);
    $task->assignees()->attach($target);
    Comment::factory()->create(['task_id' => $task->id, 'user_id' => $target->id]);

    $this->actingAs($admin);

    Livewire::test(ViewUser::class, ['record' => $target->id])
        ->assertSeeText('Assigned task status')
        ->assertSeeText('Organizations')
        ->assertSeeText('Recent comments');
});
