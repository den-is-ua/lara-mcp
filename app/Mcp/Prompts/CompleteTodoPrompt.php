<?php

namespace App\Mcp\Prompts;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Позначає наявне завдання виконаним. Потрібен id зі списку.')]
class CompleteTodoPrompt extends Prompt
{
    /**
     * Handle the prompt request.
     *
     * @return array<int, Response>
     */
    public function handle(Request $request): array
    {
        $validated = $request->validate([
            'task' => ['required', 'string', 'max:255'],
        ]);

        $task = trim((string) $validated['task']);

        if ($task === '') {
            throw ValidationException::withMessages([
                'task' => __('validation.required', ['attribute' => 'task']),
            ]);
        }

        $instructions = <<<'MARKDOWN'
            Познач завдання виконаним.

            Виклич list-todos-tool і знайди відкрите завдання, яке відповідає запиту.
            Потім виклич update-todo-tool з його id і completed=true.
            Якщо підходить кілька завдань, запитай яке саме. Якщо жодного, так і скажи.
            Не створюй нове завдання.
            MARKDOWN;

        return [
            Response::text($instructions)->asAssistant(),
            Response::text($task),
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
                name: 'task',
                description: 'Назва або опис завдання, яке треба позначити виконаним.',
                required: true,
            ),
        ];
    }
}
