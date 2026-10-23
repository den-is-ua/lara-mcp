<?php

namespace Tests\Feature\Mcp\Resources;

use App\Mcp\Resources\TodoDashboardApp;
use App\Mcp\Servers\TodoServer;
use Tests\TestCase;

class TodoDashboardAppTest extends TestCase
{
    public function test_reading_the_dashboard_returns_the_interactive_app(): void
    {
        $response = TodoServer::resource(TodoDashboardApp::class);

        $response->assertOk()
            ->assertSee('<title>Todo Dashboard App</title>')
            ->assertSee('id="run-btn"')
            ->assertSee("callServerTool('tool-name'");
    }
}
