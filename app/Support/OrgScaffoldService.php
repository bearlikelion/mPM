<?php

namespace App\Support;

use App\Models\Comment;
use App\Models\Epic;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\SprintPlanningMeeting;
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
    visibility: org # org, restricted, or public

epics:
  - key: launch
    project: APP
    name: Launch readiness
    description: Group related launch tasks.

sprints:
  - key: sprint-1
    project: APP
    name: Sprint 1
    starts_at: {$startsAt}
    ends_at: {$endsAt}
    started: false

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
                'visibility' => $project->visibility,
            ])->values()->all(),
            'epics' => $organization->projects->flatMap(fn (Project $project) => $project->epics->map(fn (Epic $epic): array => [
                'key' => Str::slug($epic->name),
                'project' => $project->key,
                'name' => $epic->name,
                'description' => $epic->description,
                'due_date' => $epic->due_date?->toDateString(),
            ]))->values()->all(),
            'sprints' => $organization->projects->flatMap(fn (Project $project) => $project->sprints->map(fn (Sprint $sprint): array => [
                'key' => Str::slug($sprint->name),
                'project' => $project->key,
                'name' => $sprint->name,
                'starts_at' => $sprint->starts_at?->toDateString(),
                'ends_at' => $sprint->ends_at?->toDateString(),
                'started' => $sprint->started_at !== null,
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
            ],
        ];
    }

    public function import(Organization $organization, string $yaml): void
    {
        $data = $this->parse($yaml);

        DB::transaction(function () use ($organization, $data): void {
            $projects = $this->importProjects($organization, $data['projects'] ?? []);
            $tags = $this->importTags($organization, $data['tags'] ?? []);
            $epics = $this->importEpics($projects, $data['epics'] ?? []);
            $sprints = $this->importSprints($projects, $data['sprints'] ?? []);
            $tasks = $this->importTasks($organization, $projects, $epics, $sprints, $data['tasks'] ?? []);

            $this->syncTaskTagsAndAssignees($organization, $tasks, $tags, $data['tasks'] ?? []);
            $this->syncBlockers($tasks, $data['tasks'] ?? []);
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
                    'due_date' => $item['due_date'] ?? null,
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

    private function nextTaskKey(Project $project): string
    {
        $project->increment('task_counter');
        $project->refresh();

        return $project->key.'-'.$project->task_counter;
    }
}
