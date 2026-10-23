<?php

namespace Tests\Feature\Ai\Tools;

use App\Ai\Tools\GetAllTodoItems;
use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class GetAllTodoItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_todos_returns_open_items_before_completed_ones(): void
    {
        $olderOpen = Todo::factory()->create([
            'title' => 'Купити молоко',
            'description' => null,
            'label' => ['home'],
        ]);
        $completed = Todo::factory()->completed()->create([
            'title' => 'Walk the dog',
            'description' => 'Evening',
            'label' => ['pet'],
        ]);
        $newerOpen = Todo::factory()->create([
            'title' => 'Write tests',
            'description' => 'Cover the list',
            'label' => ['backend', 'qa'],
            'due_date' => '2026-10-30',
        ]);

        $result = (new GetAllTodoItems)->handle(new Request);

        $this->assertStringContainsString('Купити молоко', $result);
        $this->assertSame([
            [
                'id' => $newerOpen->id,
                'title' => 'Write tests',
                'description' => 'Cover the list',
                'label' => ['backend', 'qa'],
                'due_date' => '2026-10-30',
                'completed' => false,
            ],
            [
                'id' => $olderOpen->id,
                'title' => 'Купити молоко',
                'description' => null,
                'label' => ['home'],
                'due_date' => null,
                'completed' => false,
            ],
            [
                'id' => $completed->id,
                'title' => 'Walk the dog',
                'description' => 'Evening',
                'label' => ['pet'],
                'due_date' => null,
                'completed' => true,
            ],
        ], json_decode($result, true));
    }

    public function test_completed_filter_returns_only_open_todos(): void
    {
        Todo::factory()->create([
            'title' => 'Open task',
            'description' => null,
            'label' => ['alpha'],
        ]);
        Todo::factory()->completed()->create([
            'title' => 'Finished task',
            'description' => null,
            'label' => ['beta'],
        ]);

        $result = (new GetAllTodoItems)->handle(new Request([
            'completed' => false,
        ]));

        $todos = json_decode($result, true);

        $this->assertSame(['Open task'], array_column($todos, 'title'));
        $this->assertSame([['alpha']], array_column($todos, 'label'));
        $this->assertSame([false], array_column($todos, 'completed'));
    }

    public function test_empty_list_returns_an_empty_array(): void
    {
        $result = (new GetAllTodoItems)->handle(new Request);

        $this->assertSame('[]', $result);
    }

    public function test_invalid_completed_filter_is_rejected(): void
    {
        Todo::factory()->create();

        try {
            (new GetAllTodoItems)->handle(new Request([
                'completed' => 'maybe',
            ]));

            $this->fail('An invalid completed filter was accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The completed field must be true or false.',
                $exception->validator->errors()->first('completed'),
            );
        }
    }
}
