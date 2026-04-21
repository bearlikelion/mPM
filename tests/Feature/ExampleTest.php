<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_reflects_the_product_positioning(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('A self-hosted project room that feels like your team actually lives there.')
            ->assertSee('Backlog to sprint')
            ->assertSee('Kanban, backlog views, sprint planning, and dashboard pulse');
    }
}
