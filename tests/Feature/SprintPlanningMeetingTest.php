<?php

use App\Livewire\SprintPlanningRoom;
use App\Models\Organization;
use App\Models\Project;
use App\Models\SprintPlanningItem;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskActivityNotification;
use App\Support\SprintPlanningService;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
    config(['broadcasting.default' => 'null']);
});

it('plans a sprint from voted backlog cards', function () {
    Notification::fake();

    $organization = Organization::factory()->create([
        'settings' => [
            'sprint_length_days' => 14,
            'story_points_per_sprint' => 8,
        ],
    ]);
    $project = Project::factory()->create(['organization_id' => $organization->id, 'key' => 'APP']);
    $facilitator = User::factory()->create(['default_organization_id' => $organization->id]);
    $member = User::factory()->create(['default_organization_id' => $organization->id]);

    $organization->users()->attach($facilitator, ['role' => 'org_admin', 'joined_at' => now()]);
    $organization->users()->attach($member, ['role' => 'member', 'joined_at' => now()]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'key' => 'APP-1',
        'sprint_id' => null,
        'status' => 'todo',
        'story_points' => null,
    ]);

    $service = app(SprintPlanningService::class);
    $meeting = $service->schedule($project, $facilitator, 'Sprint 1 planning', now()->toDateTimeString(), 8);

    $service->join($meeting, $member);
    $meeting = $service->begin($meeting, $facilitator);
    $item = $meeting->items()->firstOrFail();

    expect($meeting->status)->toBe('active')
        ->and($item->status)->toBe(SprintPlanningItem::STATUS_VOTING);

    $service->vote($item, $facilitator, 3);
    $service->vote($item->fresh(), $member, 5);

    $item = $item->fresh();
    expect($service->tieOptions($item)->all())->toContain(3, 5);

    $service->resolveTie($item, $facilitator, 5);
    $service->claim($item->fresh(), $member);
    $meeting = $service->complete($meeting->fresh(), $facilitator);

    $task->refresh();

    expect($meeting->status)->toBe('completed')
        ->and($meeting->sprint_id)->not->toBeNull()
        ->and($task->sprint_id)->toBe($meeting->sprint_id)
        ->and($task->story_points)->toBe(5)
        ->and($task->assignees()->whereKey($member->id)->exists())->toBeTrue();

    Notification::assertSentTo($member, TaskActivityNotification::class);
});

it('shows attendance before the websocket presence channel connects', function () {
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $facilitator = User::factory()->create(['default_organization_id' => $organization->id]);
    $member = User::factory()->create(['default_organization_id' => $organization->id]);

    $organization->users()->attach($facilitator, ['role' => 'org_admin', 'joined_at' => now()]);
    $organization->users()->attach($member, ['role' => 'member', 'joined_at' => now()]);

    $service = app(SprintPlanningService::class);
    $meeting = $service->schedule($project, $facilitator, 'Sprint 1 planning', now()->toDateTimeString(), 8);
    $service->join($meeting, $member);

    $this->actingAs($facilitator);

    Livewire::test(SprintPlanningRoom::class, ['meetingId' => $meeting->id])
        ->assertSee('2 in attendance')
        ->assertSee('realtime pending')
        ->assertDontSee('connecting');
});
