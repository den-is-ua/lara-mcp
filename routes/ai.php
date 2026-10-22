<?php

use App\Mcp\Servers\WeatherServer;
use App\Mcp\Servers\TodoServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/weather', WeatherServer::class);
Mcp::web('/mcp/todo', TodoServer::class);
