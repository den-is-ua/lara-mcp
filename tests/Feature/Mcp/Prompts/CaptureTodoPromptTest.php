<?php

namespace Tests\Feature\Mcp\Prompts;

use App\Mcp\Prompts\CaptureTodoPrompt;
use App\Mcp\Servers\TodoServer;
use Tests\TestCase;

class CaptureTodoPromptTest extends TestCase
{
    public function test_note_is_trimmed_and_asks_to_check_the_list_before_creating(): void
    {
        $response = TodoServer::prompt(CaptureTodoPrompt::class, [
            'note' => '  Купити молоко, два літри  ',
        ]);

        $response->assertOk()
            ->assertSee('list-todos-tool')
            ->assertSee('create-todo-tool')
            ->assertSee('Купити молоко, два літри');
    }

    public function test_missing_note_is_rejected(): void
    {
        TodoServer::prompt(CaptureTodoPrompt::class)
            ->assertHasErrors([
                'The note field is required.',
            ]);
    }

    public function test_whitespace_note_is_rejected(): void
    {
        TodoServer::prompt(CaptureTodoPrompt::class, [
            'note' => '   ',
        ])->assertHasErrors([
            'The note field is required.',
        ]);
    }

    public function test_note_longer_than_5000_characters_is_rejected(): void
    {
        TodoServer::prompt(CaptureTodoPrompt::class, [
            'note' => str_repeat('а', 5001),
        ])->assertHasErrors([
            'The note field must not be greater than 5000 characters.',
        ]);
    }
}
