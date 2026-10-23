<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\TodoServer;
use App\Mcp\Tools\CreateTodoTool;
use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTodoToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_todo_stores_the_title_and_notes(): void
    {
        $response = TodoServer::tool(CreateTodoTool::class, [
            'title' => '  Buy milk  ',
            'description' => '  Two liters  ',
        ]);

        $todo = Todo::query()->where('title', 'Buy milk')->first();

        $this->assertNotNull($todo);
        $this->assertModelExists($todo);
        $this->assertSame('Buy milk', $todo->title);
        $this->assertSame('Two liters', $todo->description);
        $this->assertNull($todo->due_date);
        $this->assertFalse($todo->completed);

        $response->assertOk()->assertStructuredContent([
            'id' => $todo->id,
            'title' => 'Buy milk',
            'description' => 'Two liters',
            'due_date' => null,
            'completed' => false,
        ]);
    }

    public function test_missing_title_is_rejected(): void
    {
        TodoServer::tool(CreateTodoTool::class, [
            'description' => 'Notes',
        ])->assertHasErrors([
            'The title field is required.',
        ]);

        $this->assertDatabaseCount('todos', 0);
    }

    public function test_whitespace_title_is_rejected(): void
    {
        TodoServer::tool(CreateTodoTool::class, [
            'title' => '   ',
        ])->assertHasErrors([
            'The title field is required.',
        ]);

        $this->assertDatabaseCount('todos', 0);
    }

    public function test_title_longer_than_255_characters_is_rejected(): void
    {
        TodoServer::tool(CreateTodoTool::class, [
            'title' => str_repeat('a', 256),
        ])->assertHasErrors([
            'The title field must not be greater than 255 characters.',
        ]);

        $this->assertDatabaseCount('todos', 0);
    }

    public function test_notes_longer_than_5000_characters_are_rejected(): void
    {
        TodoServer::tool(CreateTodoTool::class, [
            'title' => 'Buy milk',
            'description' => str_repeat('a', 5001),
        ])->assertHasErrors([
            'The description field must not be greater than 5000 characters.',
        ]);

        $this->assertDatabaseCount('todos', 0);
    }

    public function test_empty_notes_are_stored_as_null(): void
    {
        TodoServer::tool(CreateTodoTool::class, [
            'title' => 'Buy milk',
            'description' => '   ',
        ])->assertOk();

        $todo = Todo::query()->where('title', 'Buy milk')->first();

        $this->assertNotNull($todo);
        $this->assertNull($todo->description);
    }

    public function test_new_todo_stays_open_when_completed_is_sent(): void
    {
        TodoServer::tool(CreateTodoTool::class, [
            'title' => 'Buy milk',
            'completed' => true,
        ])->assertOk();

        $todo = Todo::query()->where('title', 'Buy milk')->first();

        $this->assertNotNull($todo);
        $this->assertFalse($todo->completed);
    }

    public function test_due_date_is_stored(): void
    {
        $response = TodoServer::tool(CreateTodoTool::class, [
            'title' => 'Buy milk',
            'due_date' => '2026-10-30',
        ]);

        $todo = Todo::query()->where('title', 'Buy milk')->first();

        $this->assertNotNull($todo);
        $this->assertSame('2026-10-30', $todo->due_date?->toDateString());

        $response->assertOk()->assertStructuredContent([
            'id' => $todo->id,
            'title' => 'Buy milk',
            'description' => null,
            'due_date' => '2026-10-30',
            'completed' => false,
        ]);
    }

    public function test_invalid_due_date_is_rejected(): void
    {
        TodoServer::tool(CreateTodoTool::class, [
            'title' => 'Buy milk',
            'due_date' => 'tomorrow',
        ])->assertHasErrors([
            'The due date field must be a valid date.',
        ]);

        $this->assertDatabaseCount('todos', 0);
    }
}
