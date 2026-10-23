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
            ->assertSee('due_date')
            ->assertSee('Купити молоко, два літри')
            ->assertDontSee('Дата виконання:');
    }

    public function test_due_date_is_included_in_the_capture_request(): void
    {
        $response = TodoServer::prompt(CaptureTodoPrompt::class, [
            'note' => 'Купити молоко',
            'due_date' => '2026-10-30',
        ]);

        $response->assertOk()
            ->assertSee('Купити молоко')
            ->assertSee('Дата виконання: 2026-10-30.');
    }

    public function test_blank_due_date_is_left_out_of_the_capture_request(): void
    {
        $response = TodoServer::prompt(CaptureTodoPrompt::class, [
            'note' => 'Купити молоко',
            'due_date' => '   ',
        ]);

        $response->assertOk()
            ->assertSee('Купити молоко')
            ->assertDontSee('Дата виконання:');
    }

    public function test_invalid_due_date_is_rejected(): void
    {
        TodoServer::prompt(CaptureTodoPrompt::class, [
            'note' => 'Купити молоко',
            'due_date' => 'tomorrow',
        ])->assertHasErrors([
            'The due date field must be a valid date.',
        ]);
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
