<?php

namespace App\Support;

use App\Models\Comment;
use App\Models\Epic;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\SprintPlanningItem;
use App\Models\SprintPlanningMeeting;
use App\Models\SprintPlanningParticipant;
use App\Models\SprintPlanningVote;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class OrgScaffoldService
{
    public function template(Organization $organization): string
    {
        $startsAt = now()->toDateString();
        $endsAt = now()->addDays($organization->sprintLengthDays() - 1)->toDateString();
        $sprintLengthDays = $organization->sprintLengthDays();
        $storyPoints = $organization->storyPointsPerSprint();

        return <<<YAML
# mPM organization scaffold
# Upload this file from Org scaffolding to create or update project data.
# Stable keys connect sections together: project keys, epic keys, sprint keys, task keys, and blocker task keys.
# Assignees must be existing organization member email addresses.

organization:
  name: "{$organization->name}"
  settings:
    sprint_length_days: {$sprintLengthDays}
    story_points_per_sprint: {$storyPoints}

projects:
  - key: APP
    name: Example App
    description: Short project description.
    avatar: https://picsum.photos/seed/mpm-app/160/160
    visibility: org # org, restricted, or public

epics:
  - key: launch
    project: APP
    name: Launch readiness
    description: Group related launch tasks.
    avatar: https://picsum.photos/seed/mpm-launch/160/160

sprints:
  - key: sprint-1
    project: APP
    name: Sprint 1
    starts_at: {$startsAt}
    ends_at: {$endsAt}
    started: false
    ended: false

tags:
  - name: feature
    color: "#8ec07c"
  - name: bug
    color: "#fb4934"

tasks:
  - key: APP-1
    project: APP
    title: Write first imported task
    description: Use YAML to bootstrap projects, epics, sprints, tasks, tags, assignees, and blockers.
    status: todo # todo, in_progress, review, or done
    priority: med # low, med, high, or crit
    story_points: 3
    epic: launch
    sprint: sprint-1
    tags: [feature]
    assignees: [person@example.com]
    blockers: []

comments:
  - task: APP-1
    user: person@example.com
    body: Imported scaffold comments appear in task history.

sprint_planning_meetings:
  - key: planning-1
    project: APP
    sprint: sprint-1
    facilitator: person@example.com
    name: Sprint planning
    status: scheduled # scheduled, active, completed, or cancelled
    scheduled_at: "{$startsAt} 10:00:00"
    story_points_limit: {$storyPoints}
    participants: [person@example.com]
    items:
      - task: APP-1
        status: pending # pending, voting, estimated, claimed, delayed, or backlog
        sort_order: 1
        selected_story_points: 3
        votes:
          person@example.com: 3
YAML;
    }

    public function export(Organization $organization): string
    {
        $organization->load('projects.epics', 'projects.sprints', 'tags');

        $tasks = Task::query()
            ->with('project', 'epic', 'sprint', 'tags', 'assignees', 'blockers')
            ->whereIn('project_id', $organization->projects->pluck('id'))
            ->orderBy('key')
            ->get();

        return Yaml::dump([
            'organization' => [
                'name' => $organization->name,
                'settings' => $organization->sprintSettings(),
            ],
            'projects' => $organization->projects->map(fn (Project $project): array => [
                'key' => $project->key,
                'name' => $project->name,
                'description' => $project->description,
                'avatar' => $project->avatar_path,
                'visibility' => $project->visibility,
            ])->values()->all(),
            'epics' => $organization->projects->flatMap(fn (Project $project) => $project->epics->map(fn (Epic $epic): array => [
                'key' => Str::slug($epic->name),
                'project' => $project->key,
                'name' => $epic->name,
                'description' => $epic->description,
                'avatar' => $epic->avatar_path,
                'due_date' => $epic->due_date?->toDateString(),
                'completed' => $epic->completed_at !== null,
            ]))->values()->all(),
            'sprints' => $organization->projects->flatMap(fn (Project $project) => $project->sprints->map(fn (Sprint $sprint): array => [
                'key' => Str::slug($sprint->name),
                'project' => $project->key,
                'name' => $sprint->name,
                'starts_at' => $sprint->starts_at?->toDateString(),
                'ends_at' => $sprint->ends_at?->toDateString(),
                'started' => $sprint->started_at !== null,
                'ended' => $sprint->ended_at !== null,
            ]))->values()->all(),
            'tags' => $organization->tags->map(fn (Tag $tag): array => [
                'name' => $tag->name,
                'color' => $tag->color,
            ])->values()->all(),
            'tasks' => $tasks->map(fn (Task $task): array => [
                'key' => $task->key,
                'project' => $task->project->key,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'story_points' => $task->story_points,
                'epic' => $task->epic ? Str::slug($task->epic->name) : null,
                'sprint' => $task->sprint ? Str::slug($task->sprint->name) : null,
                'tags' => $task->tags->pluck('name')->values()->all(),
                'assignees' => $task->assignees->pluck('email')->values()->all(),
                'blockers' => $task->blockers->pluck('key')->values()->all(),
            ])->values()->all(),
        ], 6, 2);
    }

    /**
     * @return array{valid: bool, errors: array<int, string>, counts: array<string, int>}
     */
    public function preview(Organization $organization, string $yaml): array
    {
        try {
            $data = $this->parse($yaml);
        } catch (ParseException $exception) {
            return [
                'valid' => false,
                'errors' => [$exception->getMessage()],
                'counts' => ['projects' => 0, 'epics' => 0, 'sprints' => 0, 'tasks' => 0, 'tags' => 0],
            ];
        }
        $errors = [];

        foreach (['projects', 'tasks'] as $requiredSection) {
            if (! is_array($data[$requiredSection] ?? null)) {
                $errors[] = "Missing {$requiredSection} list.";
            }
        }

        $projectKeys = collect($data['projects'] ?? [])->pluck('key')->filter();

        foreach (($data['tasks'] ?? []) as $index => $task) {
            if (! in_array($task['project'] ?? null, $projectKeys->all(), true)) {
                $errors[] = 'Task #'.($index + 1).' references an unknown project.';
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'counts' => [
                'projects' => count($data['projects'] ?? []),
                'epics' => count($data['epics'] ?? []),
                'sprints' => count($data['sprints'] ?? []),
                'tasks' => count($data['tasks'] ?? []),
                'tags' => count($data['tags'] ?? []),
                'comments' => count($data['comments'] ?? []),
                'sprint_planning_meetings' => count($data['sprint_planning_meetings'] ?? []),
            ],
        ];
    }

    public function import(Organization $organization, string $yaml): void
    {
        $data = $this->parse($yaml);

        DB::transaction(function () use ($organization, $data): void {
            $this->importOrganizationSettings($organization, $data['organization'] ?? []);

            $projects = $this->importProjects($organization, $data['projects'] ?? []);
            $tags = $this->importTags($organization, $data['tags'] ?? []);
            $epics = $this->importEpics($projects, $data['epics'] ?? []);
            $sprints = $this->importSprints($projects, $data['sprints'] ?? []);
            $tasks = $this->importTasks($organization, $projects, $epics, $sprints, $data['tasks'] ?? []);

            $this->syncTaskTagsAndAssignees($organization, $tasks, $tags, $data['tasks'] ?? []);
            $this->syncBlockers($tasks, $data['tasks'] ?? []);
            $this->importComments($organization, $tasks, $data['comments'] ?? []);
            $this->importSprintPlanningMeetings($organization, $projects, $sprints, $tasks, $data['sprint_planning_meetings'] ?? []);
        });
    }

    public function purge(Organization $organization): void
    {
        DB::transaction(function () use ($organization): void {
            $projectIds = $organization->projects()->pluck('id');
            $taskIds = Task::query()->whereIn('project_id', $projectIds)->pluck('id');
            $epicIds = Epic::query()->whereIn('project_id', $projectIds)->pluck('id');
            $commentIds = Comment::query()->whereIn('task_id', $taskIds)->pluck('id');

            Media::query()
                ->where(function ($query) use ($projectIds, $taskIds, $epicIds, $commentIds): void {
                    $query
                        ->where(fn ($q) => $q->where('model_type', Project::class)->whereIn('model_id', $projectIds))
                        ->orWhere(fn ($q) => $q->where('model_type', Task::class)->whereIn('model_id', $taskIds))
                        ->orWhere(fn ($q) => $q->where('model_type', Epic::class)->whereIn('model_id', $epicIds))
                        ->orWhere(fn ($q) => $q->where('model_type', Comment::class)->whereIn('model_id', $commentIds));
                })
                ->delete();

            SprintPlanningMeeting::query()->whereIn('project_id', $projectIds)->delete();
            $organization->tags()->delete();
            $organization->projects()->delete();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function parse(string $yaml): array
    {
        $data = Yaml::parse($yaml);

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function importOrganizationSettings(Organization $organization, array $item): void
    {
        $settings = $item['settings'] ?? null;

        if (! is_array($settings)) {
            return;
        }

        $organization->update([
            'settings' => [
                'sprint_length_days' => max(1, (int) ($settings['sprint_length_days'] ?? $organization->sprintLengthDays())),
                'story_points_per_sprint' => max(1, (int) ($settings['story_points_per_sprint'] ?? $organization->storyPointsPerSprint())),
            ],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, Project>
     */
    private function importProjects(Organization $organization, array $items): array
    {
        $projects = [];

        foreach ($items as $item) {
            $key = Str::upper((string) $item['key']);
            $projects[$key] = Project::query()->updateOrCreate(
                ['organization_id' => $organization->id, 'key' => $key],
                [
                    'name' => (string) $item['name'],
                    'description' => $item['description'] ?? null,
                    'avatar_path' => $item['avatar'] ?? $item['avatar_path'] ?? null,
                    'visibility' => $item['visibility'] ?? Project::VISIBILITY_ORG,
                ],
            );
        }

        return $projects;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, Tag>
     */
    private function importTags(Organization $organization, array $items): array
    {
        $tags = [];

        foreach ($items as $item) {
            $name = (string) $item['name'];
            $tags[$name] = Tag::query()->updateOrCreate(
                ['organization_id' => $organization->id, 'name' => $name],
                ['color' => $item['color'] ?? '#888888'],
            );
        }

        return $tags;
    }

    /**
     * @param  array<string, Project>  $projects
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, Epic>
     */
    private function importEpics(array $projects, array $items): array
    {
        $epics = [];

        foreach ($items as $item) {
            $project = $projects[Str::upper((string) $item['project'])] ?? null;

            if (! $project) {
                continue;
            }

            $key = (string) ($item['key'] ?? Str::slug((string) $item['name']));
            $epics[$key] = Epic::query()->updateOrCreate(
                ['project_id' => $project->id, 'name' => (string) $item['name']],
                [
                    'description' => $item['description'] ?? null,
                    'avatar_path' => $item['avatar'] ?? $item['avatar_path'] ?? null,
                    'due_date' => $item['due_date'] ?? null,
                    'completed_at' => Arr::get($item, 'completed') ? now()->subDays(3) : null,
                ],
            );
        }

        return $epics;
    }

    /**
     * @param  array<string, Project>  $projects
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, Sprint>
     */
    private function importSprints(array $projects, array $items): array
    {
        $sprints = [];

        foreach ($items as $item) {
            $project = $projects[Str::upper((string) $item['project'])] ?? null;

            if (! $project) {
                continue;
            }

            $key = (string) ($item['key'] ?? Str::slug((string) $item['name']));
            $sprints[$key] = Sprint::query()->updateOrCreate(
                ['project_id' => $project->id, 'name' => (string) $item['name']],
                [
                    'starts_at' => $item['starts_at'] ?? now()->toDateString(),
                    'ends_at' => $item['ends_at'] ?? now()->addDays(13)->toDateString(),
                    'started_at' => Arr::get($item, 'started') ? now() : null,
                    'ended_at' => Arr::get($item, 'ended') ? now()->subDays(1) : null,
                ],
            );
        }

        return $sprints;
    }

    /**
     * @param  array<string, Project>  $projects
     * @param  array<string, Epic>  $epics
     * @param  array<string, Sprint>  $sprints
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, Task>
     */
    private function importTasks(Organization $organization, array $projects, array $epics, array $sprints, array $items): array
    {
        $tasks = [];

        foreach ($items as $item) {
            $project = $projects[Str::upper((string) $item['project'])] ?? null;

            if (! $project) {
                continue;
            }

            $key = (string) ($item['key'] ?? $this->nextTaskKey($project));
            $tasks[$key] = Task::query()->updateOrCreate(
                ['project_id' => $project->id, 'key' => $key],
                [
                    'epic_id' => isset($item['epic']) ? ($epics[(string) $item['epic']]->id ?? null) : null,
                    'sprint_id' => isset($item['sprint']) ? ($sprints[(string) $item['sprint']]->id ?? null) : null,
                    'title' => (string) $item['title'],
                    'description' => $item['description'] ?? null,
                    'status' => $item['status'] ?? 'todo',
                    'priority' => $item['priority'] ?? 'med',
                    'story_points' => $item['story_points'] ?? null,
                ],
            );

            $project->update([
                'task_counter' => max($project->task_counter, (int) Str::after($key, $project->key.'-')),
            ]);
        }

        return $tasks;
    }

    /**
     * @param  array<string, Task>  $tasks
     * @param  array<string, Tag>  $tags
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncTaskTagsAndAssignees(Organization $organization, array $tasks, array $tags, array $items): void
    {
        foreach ($items as $item) {
            $task = isset($item['key']) ? ($tasks[(string) $item['key']] ?? null) : null;

            if (! $task) {
                continue;
            }

            $task->tags()->sync(collect($item['tags'] ?? [])->map(fn (string $name): ?int => $tags[$name]->id ?? null)->filter()->all());

            $userIds = User::query()
                ->whereIn('email', $item['assignees'] ?? [])
                ->whereHas('organizations', fn ($query) => $query->whereKey($organization->id))
                ->pluck('id')
                ->all();

            $task->assignees()->sync($userIds);
        }
    }

    /**
     * @param  array<string, Task>  $tasks
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncBlockers(array $tasks, array $items): void
    {
        foreach ($items as $item) {
            $task = isset($item['key']) ? ($tasks[(string) $item['key']] ?? null) : null;

            if (! $task) {
                continue;
            }

            $task->blockers()->sync(collect($item['blockers'] ?? [])->map(fn (string $key): ?int => $tasks[$key]->id ?? null)->filter()->all());
        }
    }

    /**
     * @param  array<string, Task>  $tasks
     * @param  array<int, array<string, mixed>>  $items
     */
    private function importComments(Organization $organization, array $tasks, array $items): void
    {
        $commentTaskIds = collect($items)
            ->map(fn (array $item): ?int => ($tasks[(string) ($item['task'] ?? '')] ?? null)?->id)
            ->filter()
            ->all();

        if ($commentTaskIds !== []) {
            Comment::query()->whereIn('task_id', $commentTaskIds)->delete();
        }

        foreach ($items as $item) {
            $task = $tasks[(string) ($item['task'] ?? '')] ?? null;
            $user = $this->organizationUser($organization, (string) ($item['user'] ?? ''));

            if (! $task || ! $user) {
                continue;
            }

            Comment::query()->create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'body' => (string) ($item['body'] ?? ''),
            ]);
        }
    }

    /**
     * @param  array<string, Project>  $projects
     * @param  array<string, Sprint>  $sprints
     * @param  array<string, Task>  $tasks
     * @param  array<int, array<string, mixed>>  $items
     */
    private function importSprintPlanningMeetings(Organization $organization, array $projects, array $sprints, array $tasks, array $items): void
    {
        foreach ($items as $item) {
            $project = $projects[Str::upper((string) ($item['project'] ?? ''))] ?? null;
            $facilitator = $this->organizationUser($organization, (string) ($item['facilitator'] ?? ''));

            if (! $project || ! $facilitator) {
                continue;
            }

            $meeting = SprintPlanningMeeting::query()->updateOrCreate(
                ['project_id' => $project->id, 'name' => (string) $item['name']],
                [
                    'facilitator_id' => $facilitator->id,
                    'sprint_id' => isset($item['sprint']) ? ($sprints[(string) $item['sprint']]->id ?? null) : null,
                    'status' => $item['status'] ?? SprintPlanningMeeting::STATUS_SCHEDULED,
                    'scheduled_at' => $item['scheduled_at'] ?? now(),
                    'story_points_limit' => $item['story_points_limit'] ?? $organization->storyPointsPerSprint(),
                    'started_at' => in_array($item['status'] ?? null, [SprintPlanningMeeting::STATUS_ACTIVE, SprintPlanningMeeting::STATUS_COMPLETED], true) ? now()->subHours(2) : null,
                    'completed_at' => ($item['status'] ?? null) === SprintPlanningMeeting::STATUS_COMPLETED ? now()->subHour() : null,
                    'cancelled_at' => ($item['status'] ?? null) === SprintPlanningMeeting::STATUS_CANCELLED ? now()->subHour() : null,
                ],
            );

            collect($item['participants'] ?? [])->each(function (string $email) use ($organization, $meeting): void {
                $user = $this->organizationUser($organization, $email);

                if (! $user) {
                    return;
                }

                SprintPlanningParticipant::query()->updateOrCreate(
                    ['sprint_planning_meeting_id' => $meeting->id, 'user_id' => $user->id],
                    [
                        'joined_at' => now()->subMinutes(90),
                        'last_seen_at' => now()->subMinutes(5),
                    ],
                );
            });

            $currentItem = null;

            foreach (($item['items'] ?? []) as $itemData) {
                $task = $tasks[(string) ($itemData['task'] ?? '')] ?? null;

                if (! $task) {
                    continue;
                }

                $status = $itemData['status'] ?? SprintPlanningItem::STATUS_PENDING;
                $planningItem = SprintPlanningItem::query()->updateOrCreate(
                    ['sprint_planning_meeting_id' => $meeting->id, 'task_id' => $task->id],
                    [
                        'assigned_user_id' => $this->organizationUser($organization, (string) ($itemData['assigned'] ?? ''))?->id,
                        'decision_by' => $this->organizationUser($organization, (string) ($itemData['decision_by'] ?? ''))?->id,
                        'status' => $status,
                        'sort_order' => (int) ($itemData['sort_order'] ?? 0),
                        'selected_story_points' => $itemData['selected_story_points'] ?? null,
                        'decided_at' => in_array($status, [SprintPlanningItem::STATUS_ESTIMATED, SprintPlanningItem::STATUS_CLAIMED, SprintPlanningItem::STATUS_DELAYED, SprintPlanningItem::STATUS_BACKLOG], true) ? now()->subMinutes(45) : null,
                    ],
                );

                $currentItem ??= $planningItem;

                foreach (($itemData['votes'] ?? []) as $email => $storyPoints) {
                    $user = $this->organizationUser($organization, (string) $email);

                    if (! $user) {
                        continue;
                    }

                    SprintPlanningVote::query()->updateOrCreate(
                        ['sprint_planning_item_id' => $planningItem->id, 'user_id' => $user->id],
                        ['story_points' => (int) $storyPoints],
                    );
                }
            }

            if ($meeting->status === SprintPlanningMeeting::STATUS_ACTIVE && $currentItem) {
                $meeting->update(['current_item_id' => $currentItem->id]);
            }
        }
    }

    private function organizationUser(Organization $organization, string $email): ?User
    {
        if ($email === '') {
            return null;
        }

        return User::query()
            ->where('email', $email)
            ->whereHas('organizations', fn ($query) => $query->whereKey($organization->id))
            ->first();
    }

    private function nextTaskKey(Project $project): string
    {
        $project->increment('task_counter');
        $project->refresh();

        return $project->key.'-'.$project->task_counter;
    }
}
