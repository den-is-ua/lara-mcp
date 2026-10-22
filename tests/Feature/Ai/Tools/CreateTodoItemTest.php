<?php

namespace Tests\Feature\Ai\Tools;

use App\Ai\Tools\CreateTodoItem;
use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class CreateTodoItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_todo_stores_the_title_and_notes(): void
    {
        $result = (new CreateTodoItem)->handle(new Request([
            'title' => '  Buy milk  ',
            'description' => '  Two liters  ',
        ]));

        $todo = Todo::query()->where('title', 'Buy milk')->first();

        $this->assertNotNull($todo);
        $this->assertModelExists($todo);
        $this->assertSame('Buy milk', $todo->title);
        $this->assertSame('Two liters', $todo->description);
        $this->assertFalse($todo->completed);
        $this->assertSame([
            'id' => $todo->id,
            'title' => 'Buy milk',
            'description' => 'Two liters',
            'completed' => false,
        ], json_decode($result, true));
    }

    public function test_missing_title_is_rejected(): void
    {
        try {
            (new CreateTodoItem)->handle(new Request([
                'description' => 'Notes',
            ]));

            $this->fail('A missing title was accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The title field is required.',
                $exception->validator->errors()->first('title'),
            );
        }

        $this->assertDatabaseCount('todos', 0);
    }

    public function test_whitespace_title_is_rejected(): void
    {
        try {
            (new CreateTodoItem)->handle(new Request([
                'title' => '   ',
            ]));

            $this->fail('A whitespace title was accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The title field is required.',
                $exception->validator->errors()->first('title'),
            );
        }

        $this->assertDatabaseCount('todos', 0);
    }

    public function test_title_longer_than_255_characters_is_rejected(): void
    {
        try {
            (new CreateTodoItem)->handle(new Request([
                'title' => str_repeat('a', 256),
            ]));

            $this->fail('A title longer than 255 characters was accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The title field must not be greater than 255 characters.',
                $exception->validator->errors()->first('title'),
            );
        }

        $this->assertDatabaseCount('todos', 0);
    }

    public function test_notes_longer_than_5000_characters_are_rejected(): void
    {
        try {
            (new CreateTodoItem)->handle(new Request([
                'title' => 'Buy milk',
                'description' => str_repeat('a', 5001),
            ]));

            $this->fail('Notes longer than 5000 characters were accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The description field must not be greater than 5000 characters.',
                $exception->validator->errors()->first('description'),
            );
        }

        $this->assertDatabaseCount('todos', 0);
    }

    public function test_empty_notes_are_stored_as_null(): void
    {
        (new CreateTodoItem)->handle(new Request([
            'title' => 'Buy milk',
            'description' => '   ',
        ]));

        $todo = Todo::query()->where('title', 'Buy milk')->first();

        $this->assertNotNull($todo);
        $this->assertNull($todo->description);
    }

    public function test_new_todo_stays_open_when_completed_is_sent(): void
    {
        (new CreateTodoItem)->handle(new Request([
            'title' => 'Buy milk',
            'completed' => true,
        ]));

        $todo = Todo::query()->where('title', 'Buy milk')->first();

        $this->assertNotNull($todo);
        $this->assertFalse($todo->completed);
    }
}
