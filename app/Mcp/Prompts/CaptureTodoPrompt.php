<?php

namespace App\Mcp\Prompts;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Записує чернетку як нове відкрите завдання. Перед створенням перевіряє список, щоб не додати дублікат.')]
class CaptureTodoPrompt extends Prompt
{
    /**
     * Handle the prompt request.
     *
     * @return array<int, Response>
     */
    public function handle(Request $request): array
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $note = trim((string) $validated['note']);

        if ($note === '') {
            throw ValidationException::withMessages([
                'note' => __('validation.required', ['attribute' => 'note']),
            ]);
        }

        $instructions = <<<'MARKDOWN'
            Запиши завдання зі слів користувача.

            Спочатку виклич list-todos-tool. Якщо таке завдання вже є, не створюй нове: повідом назву й id наявного.
            Інакше виклич create-todo-tool. Назва має бути короткою, деталі поклади в опис. Завдання лишається відкритим.
            MARKDOWN;

        return [
            Response::text($instructions)->asAssistant(),
            Response::text($note),
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
                name: 'note',
                description: 'Чернетка завдання своїми словами.',
                required: true,
            ),
        ];
    }
}
