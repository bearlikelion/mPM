<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Epic;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesSeeder::class);

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
            'registration_enabled' => true,
        ]);

        $siteAdmin->organizations()->attach($org, [
            'role' => 'org_admin',
            'joined_at' => now(),
        ]);
        $siteAdmin->update(['default_organization_id' => $org->id]);

        $members = User::factory(3)->create();
        foreach ($members as $member) {
            $org->users()->attach($member, [
                'role' => 'member',
                'joined_at' => now(),
            ]);
            $member->update(['default_organization_id' => $org->id]);
        }

        $tags = collect(['bug', 'ui', 'marketing', 'feature', 'tech-debt'])->map(
            fn (string $name) => Tag::factory()->create([
                'organization_id' => $org->id,
                'name' => $name,
            ])
        );

        foreach (['SurfsUp' => 'SURF', 'Mark Makes Games' => 'MMG'] as $projectName => $key) {
            $project = Project::factory()->create([
                'organization_id' => $org->id,
                'name' => $projectName,
                'key' => $key,
                'visibility' => Project::VISIBILITY_ORG,
            ]);

            $epic = Epic::factory()->create([
                'project_id' => $project->id,
                'name' => $projectName.' v2',
            ]);

            $sprint = Sprint::factory()->active()->create([
                'project_id' => $project->id,
                'name' => 'Sprint 1',
            ]);

            for ($i = 1; $i <= 10; $i++) {
                $counter = DB::table('projects')->where('id', $project->id)->increment('task_counter');
                $project->refresh();

                $task = Task::factory()->create([
                    'project_id' => $project->id,
                    'epic_id' => $i <= 6 ? $epic->id : null,
                    'sprint_id' => $i <= 7 ? $sprint->id : null,
                    'created_by' => $siteAdmin->id,
                    'key' => $project->key.'-'.$project->task_counter,
                ]);

                $task->assignees()->attach(
                    $members->random(rand(1, 2))->pluck('id')->all()
                );
                $task->tags()->attach($tags->random(rand(1, 2))->pluck('id')->all());

                Comment::factory(rand(0, 3))->create([
                    'task_id' => $task->id,
                    'user_id' => $members->random()->id,
                ]);
            }
        }
    }
}
