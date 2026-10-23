<?php

namespace App\Ai\Tools;

use App\Models\Todo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateTodoItem implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Оновлює назву, опис або стан виконання наявного завдання. Потрібен id із списку завдань.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:todos,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'label' => ['sometimes', 'array'],
            'label.*' => ['string', 'max:50'],
            'due_date' => ['sometimes', 'nullable', 'date'],
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

        if (array_key_exists('label', $validated)) {
            $changes['label'] = $this->labels($validated['label']);
        }

        if (array_key_exists('due_date', $validated)) {
            $changes['due_date'] = $this->dueDate($validated['due_date']);
        }

        if (array_key_exists('completed', $validated)) {
            $changes['completed'] = $request->boolean('completed');
        }

        if ($changes === []) {
            throw ValidationException::withMessages([
                'title' => 'Provide a title, description, label, due date, or completed state.',
            ]);
        }

        $todo = Todo::query()->findOrFail((int) $validated['id']);
        $todo->update($changes);

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
            'id' => $schema->integer()
                ->description('Ідентифікатор завдання зі списку.')
                ->required(),
            'title' => $schema->string()
                ->description('Нова назва завдання.'),
            'description' => $schema->string()
                ->description('Новий опис. Передай null, щоб очистити опис.')
                ->nullable(),
            'label' => $schema->array()
                ->description('Новий список лейблів. Передай порожній масив, щоб прибрати лейбли.')
                ->items($schema->string()),
            'due_date' => $schema->string()
                ->description('Нова дата виконання у форматі YYYY-MM-DD. Передай null, щоб прибрати дату.')
                ->nullable(),
            'completed' => $schema->boolean()
                ->description('true, якщо завдання виконане, і false, якщо його треба відкрити знову.'),
        ];
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
}
