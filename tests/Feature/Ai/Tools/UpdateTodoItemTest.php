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
            'label' => ['groceries'],
            'completed' => false,
        ]);

        $result = (new UpdateTodoItem)->handle(new Request([
            'id' => $todo->id,
            'completed' => true,
        ]));

        $todo->refresh();

        $this->assertSame('Buy milk', $todo->title);
        $this->assertSame('Two liters', $todo->description);
        $this->assertSame(['groceries'], $todo->label);
        $this->assertTrue($todo->completed);
        $this->assertSame([
            'id' => $todo->id,
            'title' => 'Buy milk',
            'description' => 'Two liters',
            'label' => ['groceries'],
            'due_date' => null,
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
            'label' => [' frontend ', 'review'],
        ]));

        $todo->refresh();

        $this->assertSame('New title', $todo->title);
        $this->assertSame('New notes', $todo->description);
        $this->assertSame(['frontend', 'review'], $todo->label);
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
                'Provide a title, description, label, due date, or completed state.',
                $exception->validator->errors()->first('title'),
            );
        }

        $todo->refresh();

        $this->assertSame('Keep me', $todo->title);
        $this->assertSame('Same notes', $todo->description);
        $this->assertFalse($todo->completed);
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

    public function test_due_date_can_be_set(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Buy milk',
            'due_date' => null,
        ]);

        (new UpdateTodoItem)->handle(new Request([
            'id' => $todo->id,
            'due_date' => '2026-10-30',
        ]));

        $this->assertSame('2026-10-30', $todo->refresh()->due_date?->toDateString());
    }

    public function test_null_due_date_clears_the_date(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Buy milk',
            'due_date' => '2026-10-30',
        ]);

        (new UpdateTodoItem)->handle(new Request([
            'id' => $todo->id,
            'due_date' => null,
        ]));

        $this->assertNull($todo->refresh()->due_date);
    }

    public function test_invalid_due_date_is_rejected(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Buy milk',
            'due_date' => '2026-10-30',
        ]);

        try {
            (new UpdateTodoItem)->handle(new Request([
                'id' => $todo->id,
                'due_date' => 'tomorrow',
            ]));

            $this->fail('An invalid due date was accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The due date field must be a valid date.',
                $exception->validator->errors()->first('due_date'),
            );
        }

        $this->assertSame('2026-10-30', $todo->refresh()->due_date?->toDateString());
    }

    public function test_invalid_label_payload_is_rejected(): void
    {
        $todo = Todo::factory()->create(['title' => 'Keep me']);

        try {
            (new UpdateTodoItem)->handle(new Request([
                'id' => $todo->id,
                'label' => 'urgent',
            ]));

            $this->fail('A string label payload was accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The label field must be an array.',
                $exception->validator->errors()->first('label'),
            );
        }
    }
}
