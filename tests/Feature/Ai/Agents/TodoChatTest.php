<?php

namespace Tests\Feature\Ai\Agents;

use App\Ai\Agents\TodoChat;
use Laravel\Mcp\Server\Tool;
use Tests\TestCase;

class TodoChatTest extends TestCase
{
    public function test_chat_uses_the_todo_server_tools(): void
    {
        $names = collect((new TodoChat)->tools())
            ->map(fn (Tool $tool): string => $tool->name())
            ->sort()
            ->values()
            ->all();

        $this->assertSame([
            'create-todo-tool',
            'list-todos-tool',
            'update-todo-tool',
        ], $names);
    }
}
