<?php

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\OrgScaffoldService;

it('imports projects tasks tags assignees and blockers from yaml', function () {
    $organization = Organization::factory()->create();
    $assignee = User::factory()->create(['email' => 'builder@example.com']);
    $organization->users()->attach($assignee, ['role' => 'member', 'joined_at' => now()]);

    $yaml = <<<'YAML'
projects:
  - key: APP
    name: Application
    visibility: org
epics:
  - key: launch
    project: APP
    name: Launch
sprints:
  - key: sprint-1
    project: APP
    name: Sprint 1
    starts_at: 2026-04-24
    ends_at: 2026-05-08
tags:
  - name: feature
    color: '#8ec07c'
tasks:
  - key: APP-1
    project: APP
    title: Build blocker
    status: todo
    priority: high
    story_points: 3
    tags: [feature]
    assignees: [builder@example.com]
  - key: APP-2
    project: APP
    title: Build dependent
    status: todo
    priority: med
    story_points: 5
    blockers: [APP-1]
YAML;

    $service = app(OrgScaffoldService::class);
    $preview = $service->preview($organization, $yaml);

    expect($preview['valid'])->toBeTrue();

    $service->import($organization, $yaml);

    $project = Project::query()->where('organization_id', $organization->id)->where('key', 'APP')->firstOrFail();
    $task = Task::query()->where('project_id', $project->id)->where('key', 'APP-2')->firstOrFail();

    expect($task->blockers()->where('key', 'APP-1')->exists())->toBeTrue()
        ->and(Task::query()->where('key', 'APP-1')->firstOrFail()->assignees()->whereKey($assignee->id)->exists())->toBeTrue();
});

it('purges project data without removing organization members', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->create();
    $organization->users()->attach($member, ['role' => 'member', 'joined_at' => now()]);
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    Task::factory()->create(['project_id' => $project->id]);

    app(OrgScaffoldService::class)->purge($organization);

    expect($organization->projects()->count())->toBe(0)
        ->and($organization->users()->whereKey($member->id)->exists())->toBeTrue();
});
