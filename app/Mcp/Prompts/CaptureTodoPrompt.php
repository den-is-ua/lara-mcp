<?php

namespace App\Mcp\Prompts;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Записує чернетку як нове відкрите завдання. Перед створенням перевіряє список, щоб не додати дублікат. Можна передати дату виконання.')]
class CaptureTodoPrompt extends Prompt
{
    /**
     * Handle the prompt request.
     *
     * @return array<int, Response>
     */
    public function handle(Request $request): array
    {
        $dueDate = $request->get('due_date');

        if (is_string($dueDate) && trim($dueDate) === '') {
            $request->merge(['due_date' => null]);
        }

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date'],
        ]);

        $note = trim((string) $validated['note']);

        if ($note === '') {
            throw ValidationException::withMessages([
                'note' => __('validation.required', ['attribute' => 'note']),
            ]);
        }

        $dueDate = is_string($validated['due_date'] ?? null)
            ? trim($validated['due_date'])
            : '';

        $instructions = <<<'MARKDOWN'
            Запиши завдання зі слів користувача.

            Спочатку виклич list-todos-tool. Якщо таке завдання вже є, не створюй нове: повідом назву й id наявного.
            Інакше виклич create-todo-tool. Назва має бути короткою, деталі поклади в опис. Завдання лишається відкритим.
            Якщо вказана дата виконання, передай її в due_date у форматі YYYY-MM-DD. Без дати поле due_date не передавай.
            MARKDOWN;

        $message = $dueDate === ''
            ? $note
            : "{$note}\nДата виконання: {$dueDate}.";

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
                name: 'note',
                description: 'Чернетка завдання своїми словами.',
                required: true,
            ),
            new Argument(
                name: 'due_date',
                description: 'Необовʼязкова дата виконання у форматі YYYY-MM-DD.',
                required: false,
            ),
        ];
    }
}
