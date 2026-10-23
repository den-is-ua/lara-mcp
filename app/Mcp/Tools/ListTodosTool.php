<?php

namespace App\Mcp\Tools;

use App\Models\Todo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Повертає завдання зі списку. Без параметра completed повертає всі. Відкриті завдання йдуть перед виконаними.')]
#[IsReadOnly]
class ListTodosTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'completed' => ['sometimes', 'boolean'],
        ]);

        $todos = Todo::query()
            ->when(
                array_key_exists('completed', $validated),
                fn (Builder $query): Builder => $query->where('completed', $request->boolean('completed')),
            )
            ->orderBy('completed')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Todo $todo): array => [
                'id' => $todo->id,
                'title' => $todo->title,
                'description' => $todo->description,
                'due_date' => $todo->due_date?->toDateString(),
                'completed' => $todo->completed,
            ])
            ->all();

        return Response::structured([
            'todos' => $todos,
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'completed' => $schema->boolean()
                ->description('Якщо передано, повертає лише завдання з цим станом. Пропусти, щоб отримати весь список.'),
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
            'todos' => $schema->array()
                ->items($schema->object([
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
                ]))
                ->description('Список завдань.')
                ->required(),
        ];
    }
}
