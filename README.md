# lara-mcp

This is a test project for learning MCP (Model Context Protocol).

It is a Laravel application based on the Livewire starter kit. The app keeps a small todo list and exposes that list through MCP, so you can practice registering servers, tools, and prompts, and calling them from an MCP client.

## What you can try

The todo MCP server is available over HTTP at `/mcp/todo` and as a local server named `todo`. It has three tools:

- **create-todo-tool** adds a new open todo with a title and an optional description.
- **list-todos-tool** returns todos. Leave `completed` out to get the full list (open items first), or pass it to filter by completion.
- **update-todo-tool** changes the title, description, or completed state of an existing todo. It needs the id from the list.

A second server, the weather MCP server, is available at `/mcp/weather`. It is a small sample: a current-weather tool and a describe-weather prompt. Both return placeholder text and are here to show the shape of a tool and a prompt.

The same todo data is also available in the web app. Livewire pages at `/todos` and `/chat` sit next to Laravel AI agents that can create, list, and update todos from a conversation.

## Stack

PHP 8.3, Laravel, Livewire, Flux, Laravel AI, Laravel MCP, Laravel Reverb, Fortify, Vite, and Tailwind CSS. The default database is SQLite.
