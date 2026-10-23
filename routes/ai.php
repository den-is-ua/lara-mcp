<?php

use App\Mcp\Servers\TodoServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/todo', TodoServer::class);
Mcp::local('todo', TodoServer::class);
