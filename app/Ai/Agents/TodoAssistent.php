<?php

namespace App\Ai\Agents;

use App\Models\Todo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;
use Stringable;

class TodoAssistent implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
            Ти помічник для керування ToDo List.

            Користувач передає нове завдання та список наявних завдань.

            Твої правила:
            - Визнач, чи є у списку те саме завдання.
            - Враховуй зміст, а не лише точний збіг назви.
            - "Перевірити авторизацію API" і
              "Перевірити роботу авторизації в API"
              можуть означати одне завдання.

            Правила перевірки дат:
            - Враховуй дату виконання завдання під час
              перевірки дубліката.
            - Однакова дія з різними датами виконання
              вважається різними завданнями.
            - "Завтра", "післязавтра", "сьогодні" та дні
              тижня потрібно інтерпретувати відносно поточної
              дати, якщо вона передана в контексті.
            - Якщо дата нового завдання відрізняється від
              дати наявного завдання, поверни
              already_in_list = false.
            - Якщо дата збігається і дія та сама,
              поверни already_in_list = true.
            - Якщо дату неможливо визначити або порівняти,
              не роби впевненого висновку лише за назвою.

            Правила відповіді:
            - Якщо знайдено те саме завдання:
              already_in_list = true;
              existing_todo_id = ID знайденого завдання.
            - Якщо такого завдання немає:
              already_in_list = false;
              existing_todo_id = null.
            - Поверни лише ID, який є у списку.
            - Не вигадуй ID або завдань.
            - Відповідай українською.
        PROMPT;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return Todo::query()
            ->where('completed', false)
            ->latest()
            ->take(10)
            ->get()
            ->map(fn (Todo $todo): Message => new Message('user', $this->encodeMessage([
                'id' => $todo->id,
                'title' => $todo->title,
            ])));
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

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->min(3)->max(255)->required(),
            'description' => $schema->string(),
            'already_in_list' => $schema->boolean()->required(),
            'existing_todo_id' => $schema->number(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encodeMessage(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
