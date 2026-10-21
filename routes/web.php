<?php

use App\Ai\Agents\TodoChat;
use App\Ai\Agents\TodoStream;
use Illuminate\Broadcasting\Channel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Vercel\Vercel;

Route::view('/', 'welcome')->name('home');

Route::livewire('todos', 'pages::todos')->name('todos');

Route::get('stream', function () {
    return (new TodoStream)->stream('Проаналізуй завдання...');
});

Route::view('/stream-view', 'stream');

Route::post('/chat/message', function (Request $request): Response {
    $request->validate([
        'channel' => ['required', 'uuid'],
        'messages' => ['required', 'array', 'min:1'],
        'messages.*.role' => ['required', 'string', 'in:user,assistant'],
        'messages.*.parts' => ['required', 'array'],
    ]);

    $chat = Vercel::chat($request);
    $message = $chat->message();

    if ($message === null || blank($message->content)) {
        throw ValidationException::withMessages([
            'messages' => 'A user message is required.',
        ]);
    }

    (new TodoChat)
        ->withMessages($chat->history())
        ->broadcastNow($chat, new Channel('chat.'.$request->string('channel')->toString()));

    return response()->noContent();
})->name('chat.message');

Route::view('/chat', 'chat')->name('chat');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
