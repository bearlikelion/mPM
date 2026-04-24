<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Epic;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\Project;
use App\Models\SiteInvite;
use App\Models\SiteSetting;
use App\Models\Sprint;
use App\Models\SprintPlanningItem;
use App\Models\SprintPlanningMeeting;
use App\Models\SprintPlanningParticipant;
use App\Models\SprintPlanningVote;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesSeeder::class);

        SiteSetting::factory()->create([
            'registration_enabled' => true,
            'org_creation_enabled' => true,
            'org_invites_bypass_registration' => true,
            'org_limit_per_user' => 5,
            'user_limit_per_org' => 50,
        ]);

        $siteAdmin = User::factory()->create([
            'name' => 'Mark Arneman',
            'email' => 'mark@example.test',
            'timezone' => 'America/New_York',
        ]);
        $siteAdmin->assignRole('site_admin');

        $org = Organization::factory()->create([
            'name' => 'Nerdibear',
            'slug' => 'nerdibear',
            'timezone' => 'America/New_York',
            'settings' => [
                'sprint_length_days' => 14,
                'story_points_per_sprint' => 30,
            ],
            'registration_enabled' => true,
        ]);

        $team = $this->createTeam($org, $siteAdmin);
        $tags = $this->createTags($org);

        SiteInvite::factory()
            ->count(3)
            ->for($siteAdmin, 'creator')
            ->sequence(
                ['label' => 'Product advisory invite', 'max_uses' => 12, 'used_count' => 3],
                ['label' => 'Contractor workspace trial', 'max_uses' => 5, 'used_count' => 1],
                ['label' => 'Internal QA review', 'max_uses' => null, 'used_count' => 0],
            )
            ->create();

        OrganizationInvite::factory()
            ->count(4)
            ->for($org)
            ->for($siteAdmin, 'inviter')
            ->sequence(
                ['email' => 'maya.contractor@example.test', 'role' => 'member'],
                ['email' => 'elliot.partner@example.test', 'role' => 'project_admin'],
                ['email' => 'qa.reviewer@example.test', 'role' => 'member', 'expires_at' => now()->addDays(3)],
                ['email' => 'archived.invite@example.test', 'role' => 'member', 'accepted_at' => now()->subDay()],
            )
            ->create();

        $projects = collect([
            [
                'name' => 'SurfsUp Launch',
                'key' => 'SURF',
                'description' => 'Coordinate the playable demo, creator tooling, and public release checklist for the next SurfsUp milestone.',
            ],
            [
                'name' => 'Mark Makes Games Studio',
                'key' => 'MMG',
                'description' => 'Track studio operations, content planning, and repeatable production workflows for game projects.',
            ],
            [
                'name' => 'mPM Desktop',
                'key' => 'MPM',
                'description' => 'Improve the desktop wrapper, realtime notifications, tray workflow, and cross-platform release flow.',
            ],
        ])->map(fn (array $projectData): Project => $this->createProjectGraph($org, $team, $tags, $projectData));

        $this->createSprintPlanningDemo($projects->first(), $team);
        $this->createNotifications($team, $projects->flatMap->tasks);
    }

    private function createTeam(Organization $org, User $siteAdmin): Collection
    {
        $members = collect([
            ['name' => 'Avery Quinn', 'email' => 'avery@example.test', 'timezone' => 'America/Chicago', 'role' => 'org_admin'],
            ['name' => 'Jordan Lee', 'email' => 'jordan@example.test', 'timezone' => 'America/Los_Angeles', 'role' => 'project_admin'],
            ['name' => 'Priya Shah', 'email' => 'priya@example.test', 'timezone' => 'America/New_York', 'role' => 'member'],
            ['name' => 'Sam Rivera', 'email' => 'sam@example.test', 'timezone' => 'America/Denver', 'role' => 'member'],
            ['name' => 'Morgan Chen', 'email' => 'morgan@example.test', 'timezone' => 'Europe/London', 'role' => 'member'],
        ])->map(fn (array $userData): User => tap(User::factory()->create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'timezone' => $userData['timezone'],
        ]), function (User $user) use ($org, $userData): void {
            $org->users()->attach($user, [
                'role' => $userData['role'],
                'joined_at' => now()->subDays(fake()->numberBetween(5, 90)),
            ]);

            $user->update(['default_organization_id' => $org->id]);
        }));

        $org->users()->attach($siteAdmin, [
            'role' => 'org_admin',
            'joined_at' => now()->subMonths(4),
        ]);
        $siteAdmin->update(['default_organization_id' => $org->id]);

        return collect([$siteAdmin])->merge($members);
    }

    private function createTags(Organization $org): Collection
    {
        return collect([
            ['name' => 'bug', 'color' => '#ef4444'],
            ['name' => 'customer-impact', 'color' => '#f59e0b'],
            ['name' => 'design', 'color' => '#8b5cf6'],
            ['name' => 'feature', 'color' => '#3b82f6'],
            ['name' => 'ops', 'color' => '#14b8a6'],
            ['name' => 'qa', 'color' => '#84cc16'],
            ['name' => 'split-up', 'color' => '#ec4899'],
            ['name' => 'tech-debt', 'color' => '#64748b'],
        ])->map(fn (array $tag): Tag => Tag::factory()->for($org)->create($tag));
    }

    private function createProjectGraph(Organization $org, Collection $team, Collection $tags, array $projectData): Project
    {
        $project = Project::factory()->for($org)->create([
            ...$projectData,
            'visibility' => Project::VISIBILITY_ORG,
        ]);

        $team->each(fn (User $user, int $index) => $project->members()->attach($user, [
            'role' => $index <= 1 ? 'project_admin' : 'member',
        ]));

        $epics = collect([
            ['name' => 'Onboarding and intake', 'description' => 'Make the first-run workflow clear, fast, and resilient for new project members.'],
            ['name' => 'Planning and execution', 'description' => 'Improve backlog hygiene, sprint planning, estimation, and blocker visibility.'],
            ['name' => 'Release operations', 'description' => 'Tighten the final release checklist, notification copy, and support handoff.'],
        ])->map(fn (array $epic): Epic => Epic::factory()->for($project)->create($epic));

        $sprints = collect([
            Sprint::factory()->for($project)->create([
                'name' => 'Sprint 18 - Foundation cleanup',
                'starts_at' => now()->subWeeks(4)->toDateString(),
                'ends_at' => now()->subWeeks(2)->toDateString(),
                'started_at' => now()->subWeeks(4),
                'ended_at' => now()->subWeeks(2),
            ]),
            Sprint::factory()->active()->for($project)->create([
                'name' => 'Sprint 19 - Release polish',
                'starts_at' => now()->subDays(5)->toDateString(),
                'ends_at' => now()->addDays(9)->toDateString(),
            ]),
            Sprint::factory()->for($project)->create([
                'name' => 'Sprint 20 - Customer feedback',
                'starts_at' => now()->addDays(10)->toDateString(),
                'ends_at' => now()->addDays(24)->toDateString(),
            ]),
        ]);

        $tasks = collect($this->taskTitles())->map(function (string $title, int $index) use ($project, $team, $tags, $epics, $sprints): Task {
            $project->increment('task_counter');
            $project->refresh();

            $task = Task::factory()->for($project)->create([
                'key' => $project->key.'-'.$project->task_counter,
                'title' => $title,
                'epic_id' => $index < 9 ? $epics[$index % $epics->count()]->id : null,
                'sprint_id' => $index < 8 ? $sprints[1]->id : ($index < 11 ? $sprints[2]->id : null),
                'created_by' => $team->first()->id,
                'status' => ['todo', 'in_progress', 'review', 'done'][$index % 4],
                'priority' => ['med', 'high', 'low', 'crit'][$index % 4],
                'story_points' => Task::STORY_POINTS[$index % count(Task::STORY_POINTS)],
                'due_date' => now()->addDays($index + 3)->toDateString(),
            ]);

            $task->assignees()->attach($team->random(fake()->numberBetween(1, 3))->pluck('id')->all());
            $task->tags()->attach($tags->random(fake()->numberBetween(1, 3))->pluck('id')->all());

            Comment::factory(fake()->numberBetween(1, 3))->create([
                'task_id' => $task->id,
                'user_id' => $team->random()->id,
            ]);

            return $task;
        });

        $tasks[4]->blockers()->syncWithoutDetaching([$tasks[0]->id]);
        $tasks[6]->blockers()->syncWithoutDetaching([$tasks[2]->id, $tasks[3]->id]);
        $tasks[9]->blockers()->syncWithoutDetaching([$tasks[5]->id]);

        $project->setRelation('tasks', $tasks);

        return $project;
    }

    private function createSprintPlanningDemo(Project $project, Collection $team): void
    {
        $sprint = $project->sprints()->whereNull('ended_at')->whereNotNull('started_at')->first();
        $facilitator = $team->first();

        $meeting = SprintPlanningMeeting::factory()
            ->active()
            ->for($project)
            ->for($facilitator, 'facilitator')
            ->for($sprint)
            ->create([
                'name' => 'Sprint planning: release polish',
                'scheduled_at' => now()->subHours(2),
                'story_points_limit' => 30,
            ]);

        $team->each(fn (User $user): SprintPlanningParticipant => SprintPlanningParticipant::factory()
            ->for($meeting, 'meeting')
            ->for($user)
            ->create([
                'joined_at' => now()->subMinutes(fake()->numberBetween(15, 80)),
                'last_seen_at' => now()->subMinutes(fake()->numberBetween(0, 10)),
            ]));

        $items = $project->tasks()
            ->whereIn('status', ['todo', 'in_progress', 'review'])
            ->limit(6)
            ->get()
            ->values()
            ->map(function (Task $task, int $index) use ($meeting, $team): SprintPlanningItem {
                $status = [
                    SprintPlanningItem::STATUS_VOTING,
                    SprintPlanningItem::STATUS_PENDING,
                    SprintPlanningItem::STATUS_ESTIMATED,
                    SprintPlanningItem::STATUS_CLAIMED,
                    SprintPlanningItem::STATUS_DELAYED,
                    SprintPlanningItem::STATUS_BACKLOG,
                ][$index];

                $item = SprintPlanningItem::factory()
                    ->for($meeting, 'meeting')
                    ->for($task)
                    ->create([
                        'status' => $status,
                        'sort_order' => $index + 1,
                        'assigned_user_id' => in_array($status, [SprintPlanningItem::STATUS_CLAIMED, SprintPlanningItem::STATUS_DELAYED], true) ? $team->random()->id : null,
                        'decision_by' => in_array($status, [SprintPlanningItem::STATUS_ESTIMATED, SprintPlanningItem::STATUS_CLAIMED], true) ? $team->first()->id : null,
                        'selected_story_points' => in_array($status, [SprintPlanningItem::STATUS_ESTIMATED, SprintPlanningItem::STATUS_CLAIMED], true) ? fake()->randomElement(Task::STORY_POINTS) : null,
                        'decided_at' => in_array($status, [SprintPlanningItem::STATUS_ESTIMATED, SprintPlanningItem::STATUS_CLAIMED], true) ? now()->subMinutes(fake()->numberBetween(5, 45)) : null,
                    ]);

                $team->each(fn (User $user): SprintPlanningVote => SprintPlanningVote::factory()
                    ->for($item, 'item')
                    ->for($user)
                    ->create([
                        'story_points' => fake()->randomElement([2, 3, 5, 8]),
                    ]));

                return $item;
            });

        $meeting->update(['current_item_id' => $items->first()?->id]);
    }

    private function createNotifications(Collection $team, Collection $tasks): void
    {
        $team->each(function (User $user) use ($tasks): void {
            $tasks->random(4)->each(function (Task $task, int $index) use ($user, $tasks): void {
                $user->notifications()->create([
                    'id' => (string) Str::uuid(),
                    'type' => 'App\\Notifications\\TaskActivityNotification',
                    'data' => [
                        'kind' => ['assigned', 'mentioned', 'commented', 'blocked'][$index],
                        'task_id' => $task->id,
                        'task_key' => $task->key,
                        'task_title' => $task->title,
                        'project_id' => $task->project_id,
                        'title' => fake()->randomElement([
                            'Task assigned',
                            'Mentioned in a task',
                            'New blocker added',
                            'Comment needs review',
                        ]),
                        'body' => $task->key.' needs attention: '.$task->title,
                        'url' => route('tasks.show', $task->key),
                        'actor_id' => $tasks->first()->created_by,
                        'actor_name' => 'Mark Arneman',
                    ],
                    'read_at' => $index === 0 ? null : fake()->optional(0.65)->dateTimeBetween('-2 weeks', 'now'),
                    'created_at' => now()->subHours(fake()->numberBetween(1, 72)),
                    'updated_at' => now()->subHours(fake()->numberBetween(1, 72)),
                ]);
            });
        });
    }

    /**
     * @return array<int, string>
     */
    private function taskTitles(): array
    {
        return [
            'Add searchable project picker to task intake',
            'Clarify copy for expired organization invites',
            'Wire native notification permission prompt',
            'Review mobile layout for sprint planning room',
            'Fix blocked task ordering on dashboard',
            'Create release checklist for desktop wrapper',
            'Document org admin onboarding workflow',
            'Add empty state for backlog filters',
            'Validate project avatar fallback initials',
            'Split oversized reporting dashboard task',
            'Audit timezone display in meeting history',
            'Improve task comment attachment guidance',
        ];
    }
}
