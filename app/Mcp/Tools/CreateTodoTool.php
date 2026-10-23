<?php

namespace App\Mcp\Tools;

use App\Models\Todo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Створює нове відкрите завдання. Перед створенням переглянь список, щоб не додати дублікат.')]
class CreateTodoTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date'],
        ]);

        $title = trim((string) $validated['title']);

        if ($title === '') {
            throw ValidationException::withMessages([
                'title' => __('validation.required', ['attribute' => 'title']),
            ]);
        }

        $todo = Todo::query()->create([
            'title' => $title,
            'description' => $this->notes($validated['description'] ?? null),
            'due_date' => $this->dueDate($validated['due_date'] ?? null),
        ]);

        return Response::structured($this->payload($todo));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()
                ->description('Коротка назва завдання.')
                ->required(),
            'description' => $schema->string()
                ->description('Необовʼязкові деталі завдання.')
                ->nullable(),
            'due_date' => $schema->string()
                ->description('Необовʼязкова дата виконання у форматі YYYY-MM-DD.')
                ->nullable(),
        ];
    }

    /**
     * Get the tool's output schema.
     *
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('Ідентифікатор завдання.')
                ->required(),
            'title' => $schema->string()
                ->description('Назва завдання.')
                ->required(),
            'description' => $schema->string()
                ->description('Деталі завдання.')
                ->nullable(),
            'due_date' => $schema->string()
                ->description('Дата виконання у форматі YYYY-MM-DD.')
                ->nullable(),
            'completed' => $schema->boolean()
                ->description('Чи виконане завдання.')
                ->required(),
        ];
    }

    /**
     * Store an empty note as null.
     */
    private function notes(mixed $description): ?string
    {
        if (! is_string($description)) {
            return null;
        }

        $description = trim($description);

        if ($description === '') {
            return null;
        }

        return $description;
    }

    /**
     * Store an empty due date as null.
     */
    private function dueDate(mixed $dueDate): ?string
    {
        if (! is_string($dueDate)) {
            return null;
        }

        $dueDate = trim($dueDate);

        if ($dueDate === '') {
            return null;
        }

        return $dueDate;
    }

    /**
     * @return array{id: int, title: string, description: string|null, due_date: string|null, completed: bool}
     */
    private function payload(Todo $todo): array
    {
        return [
            'id' => $todo->id,
            'title' => $todo->title,
            'description' => $todo->description,
            'due_date' => $todo->due_date?->toDateString(),
            'completed' => $todo->completed,
        ];
    }
}
