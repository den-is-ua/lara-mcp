<?php

namespace Tests\Feature\Ai\Agents;

use App\Ai\Agents\TodoChat;
use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\McpServerTool;
use Tests\TestCase;

class TodoChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_uses_the_todo_server_tools(): void
    {
        $names = collect((new TodoChat)->tools())
            ->map(fn (McpServerTool $tool): string => $tool->name())
            ->sort()
            ->values()
            ->all();

        $this->assertSame([
            'create-todo-tool',
            'list-todos-tool',
            'update-todo-tool',
        ], $names);
    }

    public function test_the_chat_agent_does_not_load_stored_todos_as_history(): void
    {
        Todo::factory()->count(3)->create();

        $this->assertSame([], [...(new TodoChat)->messages()]);
    }
}
