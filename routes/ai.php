<?php

use App\Mcp\Servers\TodoServer;
use App\Mcp\Servers\WeatherServer;
use Laravel\Mcp\Client;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/weather', WeatherServer::class);
Mcp::web('/mcp/todo', TodoServer::class);
Mcp::local('todo', TodoServer::class);

Mcp::registerClient('todo-server', fn (): Client => Client::local(PHP_BINARY, [
    base_path('artisan'),
    'mcp:start',
    'todo',
    '--no-interaction',
]));
