<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\TodoServer;
use App\Mcp\Tools\UpdateTodoTool;
use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTodoToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_completed_leaves_the_title_and_notes_unchanged(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Buy milk',
            'description' => 'Two liters',
            'completed' => false,
        ]);

        $response = TodoServer::tool(UpdateTodoTool::class, [
            'id' => $todo->id,
            'completed' => true,
        ]);

        $todo->refresh();

        $this->assertSame('Buy milk', $todo->title);
        $this->assertSame('Two liters', $todo->description);
        $this->assertTrue($todo->completed);

        $response->assertOk()->assertStructuredContent([
            'id' => $todo->id,
            'title' => 'Buy milk',
            'description' => 'Two liters',
            'completed' => true,
        ]);
    }

    public function test_updating_the_title_and_notes_trims_them(): void
    {
        $todo = Todo::factory()->completed()->create([
            'title' => 'Old title',
            'description' => 'Old notes',
        ]);

        TodoServer::tool(UpdateTodoTool::class, [
            'id' => $todo->id,
            'title' => '  New title  ',
            'description' => '  New notes  ',
        ])->assertOk();

        $todo->refresh();

        $this->assertSame('New title', $todo->title);
        $this->assertSame('New notes', $todo->description);
        $this->assertTrue($todo->completed);
    }

    public function test_null_description_clears_the_notes(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Buy milk',
            'description' => 'Two liters',
        ]);

        TodoServer::tool(UpdateTodoTool::class, [
            'id' => $todo->id,
            'description' => null,
        ])->assertOk();

        $todo->refresh();

        $this->assertSame('Buy milk', $todo->title);
        $this->assertNull($todo->description);
    }

    public function test_missing_todo_is_rejected(): void
    {
        $todo = Todo::factory()->create(['title' => 'Keep me']);

        TodoServer::tool(UpdateTodoTool::class, [
            'id' => $todo->id + 1,
            'title' => 'Changed',
        ])->assertHasErrors([
            'The selected id is invalid.',
        ]);

        $this->assertSame('Keep me', $todo->refresh()->title);
    }

    public function test_update_without_changes_is_rejected(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Keep me',
            'description' => 'Same notes',
        ]);

        TodoServer::tool(UpdateTodoTool::class, [
            'id' => $todo->id,
        ])->assertHasErrors([
            'Provide a title, description, or completed state.',
        ]);

        $todo->refresh();

        $this->assertSame('Keep me', $todo->title);
        $this->assertSame('Same notes', $todo->description);
        $this->assertFalse($todo->completed);
    }

    public function test_whitespace_title_is_rejected_and_the_todo_stays_unchanged(): void
    {
        $todo = Todo::factory()->create(['title' => 'Keep me']);

        TodoServer::tool(UpdateTodoTool::class, [
            'id' => $todo->id,
            'title' => '   ',
        ])->assertHasErrors([
            'The title field is required.',
        ]);

        $this->assertSame('Keep me', $todo->refresh()->title);
    }
}
