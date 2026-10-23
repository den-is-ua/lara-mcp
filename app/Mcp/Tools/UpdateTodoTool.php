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
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Оновлює назву, опис або стан виконання наявного завдання. Потрібен id із списку завдань.')]
#[IsIdempotent]
class UpdateTodoTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:todos,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'completed' => ['sometimes', 'boolean'],
        ]);

        $changes = [];

        if (array_key_exists('title', $validated)) {
            $title = trim((string) $validated['title']);

            if ($title === '') {
                throw ValidationException::withMessages([
                    'title' => __('validation.required', ['attribute' => 'title']),
                ]);
            }

            $changes['title'] = $title;
        }

        if (array_key_exists('description', $validated)) {
            $description = $validated['description'];
            $description = is_string($description) ? trim($description) : null;
            $changes['description'] = $description === '' ? null : $description;
        }

        if (array_key_exists('completed', $validated)) {
            $changes['completed'] = $request->boolean('completed');
        }

        if ($changes === []) {
            throw ValidationException::withMessages([
                'title' => 'Provide a title, description, or completed state.',
            ]);
        }

        $todo = Todo::query()->findOrFail((int) $validated['id']);
        $todo->update($changes);

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
            'id' => $schema->integer()
                ->description('Ідентифікатор завдання зі списку.')
                ->required(),
            'title' => $schema->string()
                ->description('Нова назва завдання.'),
            'description' => $schema->string()
                ->description('Новий опис. Передай null, щоб очистити опис.')
                ->nullable(),
            'completed' => $schema->boolean()
                ->description('true, якщо завдання виконане, і false, якщо його треба відкрити знову.'),
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
            'completed' => $schema->boolean()
                ->description('Чи виконане завдання.')
                ->required(),
        ];
    }

    /**
     * @return array{id: int, title: string, description: string|null, completed: bool}
     */
    private function payload(Todo $todo): array
    {
        return [
            'id' => $todo->id,
            'title' => $todo->title,
            'description' => $todo->description,
            'completed' => $todo->completed,
        ];
    }
}
