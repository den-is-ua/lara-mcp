<?php

namespace App\Ai\Agents;

use App\Models\Todo;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;
use Stringable;

class TodoStream implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'Ти помічник для аналізу ToDo List.';
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return Todo::query()
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($message) => new Message(
                'user', json_encode(['title' => $message->title, 'description' => $message->description, 'label' => $message->label ?? [], 'due_date' => $message->due_date?->toDateString(), 'completed' => $message->completed])
            ));
    }

    /**
     * Get the tools available to the agent.
     *
     * @return list<Agent|Tool|ProviderTool>
     */
    public function tools(): iterable
    {
        return [];
    }
}
