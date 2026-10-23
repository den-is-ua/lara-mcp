<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\CaptureTodoPrompt;
use App\Mcp\Prompts\CompleteTodoPrompt;
use App\Mcp\Prompts\PlanOpenTodosPrompt;
use App\Mcp\Tools\CreateTodoTool;
use App\Mcp\Tools\ListTodosTool;
use App\Mcp\Tools\UpdateTodoTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Todo Server')]
#[Version('0.0.1')]
#[Instructions(<<<'MARKDOWN'
    Керує списком завдань.

    Спочатку виклич list-todos-tool, щоб побачити наявні завдання та їх id. Без параметра completed повертає всі: відкриті йдуть перед виконаними.
    create-todo-tool додає нове відкрите завдання. Перед створенням перевір список, щоб не додати дублікат.
    update-todo-tool змінює назву, опис, дату виконання або стан виконання наявного завдання. Потрібен id зі списку.
    MARKDOWN)]
class TodoServer extends Server
{
    protected array $tools = [
        CreateTodoTool::class,
        ListTodosTool::class,
        UpdateTodoTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        CaptureTodoPrompt::class,
        PlanOpenTodosPrompt::class,
        CompleteTodoPrompt::class,
    ];
}
