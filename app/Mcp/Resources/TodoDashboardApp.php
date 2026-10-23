<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\AppResource;
use Laravel\Mcp\Server\Attributes\AppMeta;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('A description of what this app resource does.')]
#[AppMeta]
class TodoDashboardApp extends AppResource
{
    /**
     * Handle the app resource request.
     */
    public function handle(Request $request): Response
    {
        return Response::view('mcp.todo-dashboard-app', [
            'title' => $this->title(),
        ]);
    }
}
