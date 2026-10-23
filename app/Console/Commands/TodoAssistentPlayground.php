<?php

namespace App\Console\Commands;

use App\Ai\Agents\TodoAssistent;
use App\Models\Todo;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Ai\Responses\StructuredAgentResponse;

#[Signature('todo:ai-playground')]
#[Description('Command description')]
class TodoAssistentPlayground extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $response = (new TodoAssistent)->prompt(
            'Потрібно у середу перевірити, чи працює Телефон.',
        );

        if (! $response instanceof StructuredAgentResponse) {
            $this->error('The assistant did not return a structured response.');

            return self::FAILURE;
        }

        if (($response->structured['already_in_list'] ?? false) === true) {
            $existingTodoId = $response->structured['existing_todo_id'] ?? null;

            $this->info('This task already in list');
            $this->info('Existing task ID: '.$this->displayValue($existingTodoId));
            $this->info('DONE');

            return self::SUCCESS;
        }

        $title = $response->structured['title'] ?? null;

        if (! is_string($title)) {
            $this->error('The assistant did not return a task title.');

            return self::FAILURE;
        }

        $description = $response->structured['description'] ?? null;

        $this->info('Creating new task');

        Todo::create([
            'title' => $title,
            'description' => is_string($description) ? $description : null,
        ]);

        $this->info('DONE');

        return self::SUCCESS;
    }

    private function displayValue(mixed $value): string
    {
        if (is_int($value) || is_float($value) || is_string($value)) {
            return (string) $value;
        }

        return 'unknown';
    }
}
