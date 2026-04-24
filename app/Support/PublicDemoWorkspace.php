<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class PublicDemoWorkspace
{
    public function __construct(
        private readonly OrgScaffoldService $scaffoldService,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('demo.enabled');
    }

    public function user(): ?User
    {
        return User::query()
            ->where('email', config('demo.user_email'))
            ->first();
    }

    public function ensure(): User
    {
        $user = $this->user();

        if (! $user || ! $this->organization()?->projects()->exists()) {
            $this->reset();
        }

        return User::query()
            ->where('email', config('demo.user_email'))
            ->firstOrFail();
    }

    /**
     * @return array{organization: Organization, users: Collection<int, User>, projects: int, tasks: int}
     */
    public function reset(): array
    {
        Role::findOrCreate('org_admin');

        return DB::transaction(function (): array {
            $organization = Organization::query()->updateOrCreate(
                ['slug' => config('demo.organization_slug')],
                [
                    'name' => config('demo.organization_name'),
                    'timezone' => 'America/New_York',
                    'registration_enabled' => false,
                    'settings' => [
                        'sprint_length_days' => 14,
                        'story_points_per_sprint' => 30,
                    ],
                ],
            );

            $users = $this->resetUsers($organization);

            $this->scaffoldService->purge($organization);
            $this->scaffoldService->import($organization, $this->scaffoldYaml());

            $organization->projects()->get()->each(function ($project) use ($users): void {
                $project->members()->sync($users->mapWithKeys(fn (User $user, int $index): array => [
                    $user->id => ['role' => $index <= 1 ? 'project_admin' : 'member'],
                ])->all());
            });

            $users->each(fn (User $user) => $user->notifications()->delete());
            $organization->invites()->delete();

            return [
                'organization' => $organization->refresh(),
                'users' => $users,
                'projects' => $organization->projects()->count(),
                'tasks' => $organization->tasks()->count(),
            ];
        });
    }

    private function organization(): ?Organization
    {
        return Organization::query()
            ->where('slug', config('demo.organization_slug'))
            ->first();
    }

    /**
     * @return Collection<int, User>
     */
    private function resetUsers(Organization $organization): Collection
    {
        $users = collect($this->demoUsers())->map(function (array $userData) use ($organization): User {
            $user = User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'timezone' => $userData['timezone'],
                    'password' => Hash::make(Str::password(32)),
                    'email_verified_at' => now(),
                    'default_organization_id' => $organization->id,
                ],
            );

            $user->organizations()->syncWithoutDetaching([
                $organization->id => [
                    'role' => $userData['role'],
                    'joined_at' => now()->subDays($userData['joined_days_ago']),
                ],
            ]);

            return $user;
        });

        $extraUserIds = $organization->users()
            ->whereNotIn('users.email', $users->pluck('email'))
            ->pluck('users.id');

        if ($extraUserIds->isNotEmpty()) {
            $organization->users()->detach($extraUserIds);
        }

        $users->first()?->assignRole('org_admin');

        return $users->values();
    }

    private function scaffoldYaml(): string
    {
        return File::get(database_path('seeders/demo-scaffold.yml'));
    }

    /**
     * @return array<int, array{name: string, email: string, timezone: string, role: string, joined_days_ago: int}>
     */
    private function demoUsers(): array
    {
        return [
            [
                'name' => config('demo.user_name'),
                'email' => config('demo.user_email'),
                'timezone' => 'America/New_York',
                'role' => 'org_admin',
                'joined_days_ago' => 90,
            ],
            [
                'name' => 'Casey Demo',
                'email' => 'casey.demo@example.test',
                'timezone' => 'America/Chicago',
                'role' => 'project_admin',
                'joined_days_ago' => 45,
            ],
            [
                'name' => 'Riley Demo',
                'email' => 'riley.demo@example.test',
                'timezone' => 'America/Los_Angeles',
                'role' => 'member',
                'joined_days_ago' => 31,
            ],
            [
                'name' => 'Morgan Demo',
                'email' => 'morgan.demo@example.test',
                'timezone' => 'Europe/London',
                'role' => 'member',
                'joined_days_ago' => 22,
            ],
            [
                'name' => 'Jordan Demo',
                'email' => 'jordan.demo@example.test',
                'timezone' => 'America/Denver',
                'role' => 'member',
                'joined_days_ago' => 18,
            ],
        ];
    }
}
