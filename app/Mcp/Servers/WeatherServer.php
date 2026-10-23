<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\DescribeWeatherPrompt;
use App\Mcp\Tools\CurrentWeatherTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Tool;

class WeatherServer extends Server
{
    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        CurrentWeatherTool::class,
    ];

    protected array $prompts = [
        DescribeWeatherPrompt::class,
    ];
}
