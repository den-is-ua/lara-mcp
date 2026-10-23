<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\TodoServer;
use App\Mcp\Tools\ListTodosTool;
use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTodosToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_todos_returns_open_items_before_completed_ones(): void
    {
        $olderOpen = Todo::factory()->create([
            'title' => 'Купити молоко',
            'description' => null,
        ]);
        $completed = Todo::factory()->completed()->create([
            'title' => 'Walk the dog',
            'description' => 'Evening',
        ]);
        $newerOpen = Todo::factory()->create([
            'title' => 'Write tests',
            'description' => 'Cover the list',
        ]);

        $response = TodoServer::tool(ListTodosTool::class);

        $response->assertOk()->assertStructuredContent([
            'todos' => [
                [
                    'id' => $newerOpen->id,
                    'title' => 'Write tests',
                    'description' => 'Cover the list',
                    'completed' => false,
                ],
                [
                    'id' => $olderOpen->id,
                    'title' => 'Купити молоко',
                    'description' => null,
                    'completed' => false,
                ],
                [
                    'id' => $completed->id,
                    'title' => 'Walk the dog',
                    'description' => 'Evening',
                    'completed' => true,
                ],
            ],
        ]);
    }

    public function test_completed_filter_returns_only_open_todos(): void
    {
        $open = Todo::factory()->create([
            'title' => 'Open task',
            'description' => null,
        ]);
        Todo::factory()->completed()->create([
            'title' => 'Finished task',
            'description' => null,
        ]);

        $response = TodoServer::tool(ListTodosTool::class, [
            'completed' => false,
        ]);

        $response->assertOk()->assertStructuredContent([
            'todos' => [
                [
                    'id' => $open->id,
                    'title' => 'Open task',
                    'description' => null,
                    'completed' => false,
                ],
            ],
        ]);
    }

    public function test_completed_filter_returns_only_finished_todos(): void
    {
        Todo::factory()->create([
            'title' => 'Open task',
            'description' => null,
        ]);
        $finished = Todo::factory()->completed()->create([
            'title' => 'Finished task',
            'description' => null,
        ]);

        $response = TodoServer::tool(ListTodosTool::class, [
            'completed' => true,
        ]);

        $response->assertOk()->assertStructuredContent([
            'todos' => [
                [
                    'id' => $finished->id,
                    'title' => 'Finished task',
                    'description' => null,
                    'completed' => true,
                ],
            ],
        ]);
    }

    public function test_empty_list_returns_an_empty_array(): void
    {
        $response = TodoServer::tool(ListTodosTool::class);

        $response->assertOk()->assertStructuredContent([
            'todos' => [],
        ]);
    }

    public function test_invalid_completed_filter_is_rejected(): void
    {
        Todo::factory()->create();

        $response = TodoServer::tool(ListTodosTool::class, [
            'completed' => 'maybe',
        ]);

        $response->assertHasErrors([
            'The completed field must be true or false.',
        ]);
    }
}
