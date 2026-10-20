<?php

namespace App\Console\Commands;

use App\Models\Todo;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Ai\Agents\TodoAssistent;
use function dump;

#[Signature('todo:ai-playground')]
#[Description('Command description')]
class TodoAssistentPlayground extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $response = (new TodoAssistent)->prompt(
            'Потрібно у середу перевірити, чи працює Телефон.',
        );
        
        if ($response->structured['already_in_list']) {
            $this->info('This task already in list');
            $this->info('Existing task ID: ' . $response->structured['existing_todo_id']);
        } else {
            $this->info('Creating new task');
            Todo::create([
                'title' => $response->structured['title'],
                'description' => $response->structured['description'] ?? null
            ]);
        }
       
        $this->info('DONE');
    }
}
