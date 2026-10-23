<?php

use App\Mcp\Servers\TodoServer;
use App\Mcp\Servers\WeatherServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/weather', WeatherServer::class);
Mcp::web('/mcp/todo', TodoServer::class);
Mcp::local('todo', TodoServer::class);
