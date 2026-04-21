<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response
            ->assertOk()
            ->assertSee('my open tasks')
            ->assertSee('active epics');
    }

    public function test_recent_activity_includes_task_and_user_links(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'default_organization_id' => $organization->id,
        ]);
        $organization->users()->attach($user, [
            'role' => 'org_admin',
            'joined_at' => now(),
        ]);

        $project = Project::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'key' => 'MPM-101',
            'title' => 'Tighten dashboard activity links',
            'status' => 'done',
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'body' => 'Linked recent activity should be navigable.',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee(route('tasks.show', $task->key), false)
            ->assertSee(route('users.show', $user), false)
            ->assertSee($comment->body)
            ->assertSee($task->title);
    }

    public function test_org_admin_can_visit_manager_page_and_see_analytics(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Nerdibear',
        ]);
        $admin = User::factory()->create([
            'default_organization_id' => $organization->id,
        ]);
        $member = User::factory()->create([
            'name' => 'Teammate One',
        ]);

        $organization->users()->attach($admin, [
            'role' => 'org_admin',
            'joined_at' => now(),
        ]);
        $organization->users()->attach($member, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $project = Project::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Core Platform',
        ]);

        $openTask = Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'in_progress',
            'title' => 'Review manager metrics',
        ]);
        $openTask->assignees()->attach($member);

        $doneTask = Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'done',
            'title' => 'Ship reporting widgets',
            'updated_at' => now()->subDays(2),
        ]);
        $doneTask->assignees()->attach($member);

        Comment::factory()->create([
            'task_id' => $openTask->id,
            'user_id' => $member->id,
            'body' => 'Work is moving.',
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('manager'), false);

        $response = $this->actingAs($admin)->get(route('manager'));

        $response
            ->assertOk()
            ->assertSee("everyone's workload", false)
            ->assertSee('Teammate One')
            ->assertSee(route('users.show', $member), false)
            ->assertSee(route('kanban', ['assignee' => $member->id]), false)
            ->assertSee(route('kanban', ['project' => $project->id]), false);
    }

    public function test_non_org_admin_cannot_visit_manager_page_and_does_not_see_link(): void
    {
        $organization = Organization::factory()->create();
        $member = User::factory()->create([
            'default_organization_id' => $organization->id,
        ]);

        $organization->users()->attach($member, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('manager'), false);

        $this->actingAs($member)
            ->get(route('manager'))
            ->assertForbidden();
    }
}
