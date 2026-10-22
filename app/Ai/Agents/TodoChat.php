<?php

namespace App\Ai\Agents;

use App\Ai\Tools\CreateTodoItem;
use App\Ai\Tools\GetAllTodoItems;
use App\Ai\Tools\UpdateTodoItem;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;

class TodoChat implements Agent, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return <<<'PROMPT'
            Ти — AI-помічник для керування списком завдань ToDo.

            Відповідай українською мовою.
            Допомагай користувачу планувати й аналізувати завдання.
            Відповідай чітко та лаконічно.

            Для роботи зі списком використовуй інструменти.
            Перед створенням або зміною спочатку переглянь список,
            щоб не дублювати завдання і взяти правильний id.
            Стверджуй, що завдання створене або змінене, лише
            після успішної відповіді інструмента.
            Не вигадуй id і не змінюй завдання без запиту.
        PROMPT;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return list<Agent|Tool|ProviderTool>
     */
    public function tools(): iterable
    {
        return [
            new GetAllTodoItems,
            new CreateTodoItem,
            new UpdateTodoItem,
        ];
    }
}
