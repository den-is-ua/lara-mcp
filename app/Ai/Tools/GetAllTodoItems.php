<?php

namespace App\Ai\Tools;

use App\Models\Todo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetAllTodoItems implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Повертає завдання зі списку. Без параметра completed повертає всі. Відкриті завдання йдуть перед виконаними.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
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
                'label' => $todo->label ?? [],
                'due_date' => $todo->due_date?->toDateString(),
                'completed' => $todo->completed,
            ])
            ->all();

        return json_encode($todos, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the tool's schema definition.
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
}
