<?php

namespace Tests\Feature\Ai\Agents;

use App\Ai\Agents\TodoChat;
use App\Ai\Tools\CreateTodoItem;
use App\Ai\Tools\GetAllTodoItems;
use App\Ai\Tools\UpdateTodoItem;
use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_chat_agent_can_list_create_and_update_todos(): void
    {
        $tools = [...(new TodoChat)->tools()];

        $this->assertCount(3, $tools);
        $this->assertInstanceOf(GetAllTodoItems::class, $tools[0]);
        $this->assertInstanceOf(CreateTodoItem::class, $tools[1]);
        $this->assertInstanceOf(UpdateTodoItem::class, $tools[2]);
    }

    public function test_the_chat_agent_does_not_load_stored_todos_as_history(): void
    {
        Todo::factory()->count(3)->create();

        $this->assertSame([], [...(new TodoChat)->messages()]);
    }
}
