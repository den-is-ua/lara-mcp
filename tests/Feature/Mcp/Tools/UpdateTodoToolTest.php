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
            'due_date' => null,
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
            'Provide a title, description, due date, or completed state.',
        ]);

        $todo->refresh();

        $this->assertSame('Keep me', $todo->title);
        $this->assertSame('Same notes', $todo->description);
        $this->assertFalse($todo->completed);
    }

    public function test_marking_a_todo_incomplete_leaves_the_title_and_notes_unchanged(): void
    {
        $todo = Todo::factory()->completed()->create([
            'title' => 'Buy milk',
            'description' => 'Two liters',
        ]);

        $response = TodoServer::tool(UpdateTodoTool::class, [
            'id' => $todo->id,
            'completed' => false,
        ]);

        $todo->refresh();

        $this->assertFalse($todo->completed);
        $this->assertSame('Buy milk', $todo->title);
        $this->assertSame('Two liters', $todo->description);

        $response->assertOk()->assertStructuredContent([
            'id' => $todo->id,
            'title' => 'Buy milk',
            'description' => 'Two liters',
            'due_date' => null,
            'completed' => false,
        ]);
    }

    public function test_blank_description_clears_the_notes(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Buy milk',
            'description' => 'Two liters',
        ]);

        TodoServer::tool(UpdateTodoTool::class, [
            'id' => $todo->id,
            'description' => '   ',
        ])->assertOk();

        $todo->refresh();

        $this->assertSame('Buy milk', $todo->title);
        $this->assertNull($todo->description);
        $this->assertFalse($todo->completed);
    }

    public function test_title_longer_than_255_characters_is_rejected(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Keep me',
            'description' => 'Same notes',
        ]);

        TodoServer::tool(UpdateTodoTool::class, [
            'id' => $todo->id,
            'title' => str_repeat('a', 256),
        ])->assertHasErrors([
            'The title field must not be greater than 255 characters.',
        ]);

        $todo->refresh();

        $this->assertSame('Keep me', $todo->title);
        $this->assertSame('Same notes', $todo->description);
    }

    public function test_notes_longer_than_5000_characters_are_rejected(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Keep me',
            'description' => 'Same notes',
        ]);

        TodoServer::tool(UpdateTodoTool::class, [
            'id' => $todo->id,
            'description' => str_repeat('a', 5001),
        ])->assertHasErrors([
            'The description field must not be greater than 5000 characters.',
        ]);

        $todo->refresh();

        $this->assertSame('Keep me', $todo->title);
        $this->assertSame('Same notes', $todo->description);
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

    public function test_due_date_can_be_set(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Buy milk',
            'description' => 'Two liters',
            'due_date' => null,
        ]);

        TodoServer::tool(UpdateTodoTool::class, [
            'id' => $todo->id,
            'due_date' => '2026-10-30',
        ])->assertOk()->assertStructuredContent([
            'id' => $todo->id,
            'title' => 'Buy milk',
            'description' => 'Two liters',
            'due_date' => '2026-10-30',
            'completed' => false,
        ]);

        $this->assertSame('2026-10-30', $todo->refresh()->due_date?->toDateString());
    }

    public function test_null_due_date_clears_the_date(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Buy milk',
            'due_date' => '2026-10-30',
        ]);

        TodoServer::tool(UpdateTodoTool::class, [
            'id' => $todo->id,
            'due_date' => null,
        ])->assertOk();

        $this->assertNull($todo->refresh()->due_date);
    }

    public function test_invalid_due_date_is_rejected(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Buy milk',
            'due_date' => '2026-10-30',
        ]);

        TodoServer::tool(UpdateTodoTool::class, [
            'id' => $todo->id,
            'due_date' => 'tomorrow',
        ])->assertHasErrors([
            'The due date field must be a valid date.',
        ]);

        $this->assertSame('2026-10-30', $todo->refresh()->due_date?->toDateString());
    }
}
