<?php

namespace Tests\Feature\Ai\Tools;

use App\Ai\Tools\UpdateTodoItem;
use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class UpdateTodoItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_completed_leaves_the_title_and_notes_unchanged(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Buy milk',
            'description' => 'Two liters',
            'completed' => false,
        ]);

        $result = (new UpdateTodoItem)->handle(new Request([
            'id' => $todo->id,
            'completed' => true,
        ]));

        $todo->refresh();

        $this->assertSame('Buy milk', $todo->title);
        $this->assertSame('Two liters', $todo->description);
        $this->assertTrue($todo->completed);
        $this->assertSame([
            'id' => $todo->id,
            'title' => 'Buy milk',
            'description' => 'Two liters',
            'completed' => true,
        ], json_decode($result, true));
    }

    public function test_updating_the_title_and_notes_trims_them(): void
    {
        $todo = Todo::factory()->completed()->create([
            'title' => 'Old title',
            'description' => 'Old notes',
        ]);

        (new UpdateTodoItem)->handle(new Request([
            'id' => $todo->id,
            'title' => '  New title  ',
            'description' => '  New notes  ',
        ]));

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

        (new UpdateTodoItem)->handle(new Request([
            'id' => $todo->id,
            'description' => null,
        ]));

        $todo->refresh();

        $this->assertSame('Buy milk', $todo->title);
        $this->assertNull($todo->description);
    }

    public function test_missing_todo_is_rejected(): void
    {
        $todo = Todo::factory()->create(['title' => 'Keep me']);

        try {
            (new UpdateTodoItem)->handle(new Request([
                'id' => $todo->id + 1,
                'title' => 'Changed',
            ]));

            $this->fail('A missing todo was updated.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The selected id is invalid.',
                $exception->validator->errors()->first('id'),
            );
        }

        $this->assertSame('Keep me', $todo->refresh()->title);
    }

    public function test_update_without_changes_is_rejected(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Keep me',
            'description' => 'Same notes',
        ]);

        try {
            (new UpdateTodoItem)->handle(new Request([
                'id' => $todo->id,
            ]));

            $this->fail('An update without changes was accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Provide a title, description, or completed state.',
                $exception->validator->errors()->first('title'),
            );
        }

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

        $result = (new UpdateTodoItem)->handle(new Request([
            'id' => $todo->id,
            'completed' => false,
        ]));

        $todo->refresh();

        $this->assertFalse($todo->completed);
        $this->assertSame('Buy milk', $todo->title);
        $this->assertSame('Two liters', $todo->description);
        $this->assertFalse(json_decode($result, true)['completed']);
    }

    public function test_blank_description_clears_the_notes(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Buy milk',
            'description' => 'Two liters',
        ]);

        (new UpdateTodoItem)->handle(new Request([
            'id' => $todo->id,
            'description' => '   ',
        ]));

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

        try {
            (new UpdateTodoItem)->handle(new Request([
                'id' => $todo->id,
                'title' => str_repeat('a', 256),
            ]));

            $this->fail('A title longer than 255 characters was accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The title field must not be greater than 255 characters.',
                $exception->validator->errors()->first('title'),
            );
        }

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

        try {
            (new UpdateTodoItem)->handle(new Request([
                'id' => $todo->id,
                'description' => str_repeat('a', 5001),
            ]));

            $this->fail('Notes longer than 5000 characters were accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The description field must not be greater than 5000 characters.',
                $exception->validator->errors()->first('description'),
            );
        }

        $todo->refresh();

        $this->assertSame('Keep me', $todo->title);
        $this->assertSame('Same notes', $todo->description);
    }

    public function test_whitespace_title_is_rejected_and_the_todo_stays_unchanged(): void
    {
        $todo = Todo::factory()->create(['title' => 'Keep me']);

        try {
            (new UpdateTodoItem)->handle(new Request([
                'id' => $todo->id,
                'title' => '   ',
            ]));

            $this->fail('A whitespace title was accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The title field is required.',
                $exception->validator->errors()->first('title'),
            );
        }

        $this->assertSame('Keep me', $todo->refresh()->title);
    }
}
