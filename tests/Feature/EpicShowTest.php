<?php

namespace Tests\Feature;

use App\Models\Epic;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EpicShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_epic_show_lists_sprints_with_tasks_in_this_epic(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['default_organization_id' => $organization->id]);
        $organization->users()->attach($user, ['role' => 'member', 'joined_at' => now()]);

        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $epic = Epic::factory()->create(['project_id' => $project->id, 'name' => 'Onboarding arc']);
        $otherEpic = Epic::factory()->create(['project_id' => $project->id]);

        $sprintA = Sprint::factory()->create([
            'project_id' => $project->id,
            'name' => 'Sprint A',
            'starts_at' => now()->subDays(20),
            'ends_at' => now()->subDays(6),
            'started_at' => now()->subDays(20),
            'ended_at' => now()->subDays(6),
        ]);
        $sprintB = Sprint::factory()->create([
            'project_id' => $project->id,
            'name' => 'Sprint B',
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(9),
            'started_at' => now()->subDays(5),
        ]);
        $unrelatedSprint = Sprint::factory()->create([
            'project_id' => $project->id,
            'name' => 'Sprint C',
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'epic_id' => $epic->id,
            'sprint_id' => $sprintA->id,
            'title' => 'Wire intake',
            'status' => 'done',
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'epic_id' => $epic->id,
            'sprint_id' => $sprintB->id,
            'title' => 'Add empty state',
            'status' => 'in_progress',
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'epic_id' => $otherEpic->id,
            'sprint_id' => $unrelatedSprint->id,
            'title' => 'Should not appear',
        ]);

        $response = $this->actingAs($user)->get(route('epics.show', $epic))->assertOk();

        $response->assertSee('Onboarding arc');
        $response->assertSee('Sprint A');
        $response->assertSee('Sprint B');
        $response->assertDontSee('Sprint C');
        $response->assertSee('Wire intake');
        $response->assertSee('Add empty state');
        $response->assertDontSee('Should not appear');
    }

    public function test_epic_show_denies_access_to_users_outside_organization(): void
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $epic = Epic::factory()->create(['project_id' => $project->id]);

        $outsider = User::factory()->create();

        $this->actingAs($outsider)->get(route('epics.show', $epic))->assertForbidden();
    }
}
