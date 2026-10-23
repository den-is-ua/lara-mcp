<?php

namespace Tests\Feature\Mcp\Prompts;

use App\Mcp\Prompts\CompleteTodoPrompt;
use App\Mcp\Servers\TodoServer;
use Tests\TestCase;

class CompleteTodoPromptTest extends TestCase
{
    public function test_task_is_trimmed_and_asks_to_mark_the_matching_todo_complete(): void
    {
        $response = TodoServer::prompt(CompleteTodoPrompt::class, [
            'task' => '  Купити молоко  ',
        ]);

        $response->assertOk()
            ->assertSee('list-todos-tool')
            ->assertSee('update-todo-tool')
            ->assertSee('completed=true')
            ->assertSee('Купити молоко');
    }

    public function test_missing_task_is_rejected(): void
    {
        TodoServer::prompt(CompleteTodoPrompt::class)
            ->assertHasErrors([
                'The task field is required.',
            ]);
    }

    public function test_whitespace_task_is_rejected(): void
    {
        TodoServer::prompt(CompleteTodoPrompt::class, [
            'task' => '   ',
        ])->assertHasErrors([
            'The task field is required.',
        ]);
    }

    public function test_task_longer_than_255_characters_is_rejected(): void
    {
        TodoServer::prompt(CompleteTodoPrompt::class, [
            'task' => str_repeat('а', 256),
        ])->assertHasErrors([
            'The task field must not be greater than 255 characters.',
        ]);
    }
}
