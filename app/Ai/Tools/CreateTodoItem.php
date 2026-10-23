<?php

namespace App\Ai\Tools;

use App\Models\Todo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateTodoItem implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Створює нове відкрите завдання. Перед створенням переглянь список, щоб не додати дублікат.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'label' => ['sometimes', 'array'],
            'label.*' => ['string', 'max:50'],
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
            'label' => $this->labels($validated['label'] ?? []),
            'due_date' => $this->dueDate($validated['due_date'] ?? null),
        ]);

        return json_encode([
            'id' => $todo->id,
            'title' => $todo->title,
            'description' => $todo->description,
            'label' => $todo->label ?? [],
            'due_date' => $todo->due_date?->toDateString(),
            'completed' => $todo->completed,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the tool's schema definition.
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
            'label' => $schema->array()
                ->description('Список лейблів завдання.')
                ->items($schema->string()),
            'due_date' => $schema->string()
                ->description('Необовʼязкова дата виконання у форматі YYYY-MM-DD.')
                ->nullable(),
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
     * Normalize labels before storing.
     *
     * @return array<int, string>
     */
    private function labels(mixed $labels): array
    {
        if (! is_array($labels)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(
                fn (mixed $label): string => is_string($label) ? trim($label) : '',
                $labels,
            ),
            fn (string $label): bool => $label !== '',
        )));
    }
}
