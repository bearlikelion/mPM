<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RichTextCastTest extends TestCase
{
    use RefreshDatabase;

    public function test_description_is_sanitized_on_save(): void
    {
        $task = $this->makeTask();

        $task->description = '<p>hello <script>alert(1)</script><strong>world</strong></p>';
        $task->save();

        $this->assertStringNotContainsString('<script', (string) $task->refresh()->description);
        $this->assertStringContainsString('<strong>world</strong>', (string) $task->description);
    }

    public function test_mention_spans_are_preserved(): void
    {
        $task = $this->makeTask();

        $task->description = '<p>hi <span data-mention="true" data-user-id="42" class="mention">@Mark</span></p>';
        $task->save();

        $description = (string) $task->refresh()->description;
        $this->assertStringContainsString('data-user-id="42"', $description);
        $this->assertStringContainsString('class="mention"', $description);
    }

    public function test_code_block_language_class_is_preserved(): void
    {
        $task = $this->makeTask();

        $task->description = '<pre><code class="language-php">echo 1;</code></pre>';
        $task->save();

        $this->assertStringContainsString('language-php', (string) $task->refresh()->description);
    }

    public function test_empty_value_normalizes_to_null(): void
    {
        $task = $this->makeTask();

        $task->description = '   <p></p>  ';
        $task->save();

        $this->assertNull($task->refresh()->description);
    }

    private function makeTask(): Task
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();
        $organization->users()->attach($user);

        return Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);
    }
}
