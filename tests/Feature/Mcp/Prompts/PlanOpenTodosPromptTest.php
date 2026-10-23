<?php

namespace Tests\Feature\Mcp\Prompts;

use App\Mcp\Prompts\PlanOpenTodosPrompt;
use App\Mcp\Servers\TodoServer;
use Tests\TestCase;

class PlanOpenTodosPromptTest extends TestCase
{
    public function test_plan_asks_for_open_todos_without_changing_them(): void
    {
        $response = TodoServer::prompt(PlanOpenTodosPrompt::class);

        $response->assertOk()
            ->assertSee('list-todos-tool')
            ->assertSee('completed=false')
            ->assertSee('Не створюй і не змінюй завдання.')
            ->assertSee('Склади план з відкритих завдань.')
            ->assertDontSee('Зосередься на:');
    }

    public function test_focus_is_included_in_the_plan_request(): void
    {
        $response = TodoServer::prompt(PlanOpenTodosPrompt::class, [
            'focus' => '  робота  ',
        ]);

        $response->assertOk()
            ->assertSee('Зосередься на: робота.');
    }

    public function test_blank_focus_is_left_out_of_the_plan_request(): void
    {
        $response = TodoServer::prompt(PlanOpenTodosPrompt::class, [
            'focus' => '   ',
        ]);

        $response->assertOk()
            ->assertSee('Склади план з відкритих завдань.')
            ->assertDontSee('Зосередься на:');
    }

    public function test_focus_longer_than_255_characters_is_rejected(): void
    {
        TodoServer::prompt(PlanOpenTodosPrompt::class, [
            'focus' => str_repeat('а', 256),
        ])->assertHasErrors([
            'The focus field must not be greater than 255 characters.',
        ]);
    }
}
