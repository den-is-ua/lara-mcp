<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Складає план з відкритих завдань. Список не змінює.')]
class PlanOpenTodosPrompt extends Prompt
{
    /**
     * Handle the prompt request.
     *
     * @return array<int, Response>
     */
    public function handle(Request $request): array
    {
        $validated = $request->validate([
            'focus' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $focus = trim((string) ($validated['focus'] ?? ''));

        $instructions = <<<'MARKDOWN'
            Ти плануєш день за відкритими завданнями.

            Спочатку виклич list-todos-tool з параметром completed=false.
            Запропонуй порядок лише з отриманих завдань.
            Не створюй і не змінюй завдання.
            Якщо відкритих завдань немає, так і скажи.
            MARKDOWN;

        $message = $focus === ''
            ? 'Склади план з відкритих завдань.'
            : "Склади план з відкритих завдань. Зосередься на: {$focus}.";

        return [
            Response::text($instructions)->asAssistant(),
            Response::text($message),
        ];
    }

    /**
     * Get the prompt's arguments.
     *
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'focus',
                description: 'Необовʼязковий акцент плану, наприклад «робота» або «дім».',
                required: false,
            ),
        ];
    }
}
