<?php

use Illuminate\Support\Facades\Route;
use App\Ai\Agents\TodoStream;
use App\Ai\Agents\TodoChat;
use Laravel\Ai\Vercel\Vercel;
use Illuminate\Http\Request;

Route::view('/', 'welcome')->name('home');

Route::livewire('todos', 'pages::todos')->name('todos');

Route::get('stream', function () {
    return (new TodoStream)->stream('Проаналізуй завдання...');
});

Route::view('/stream-view', 'stream');

Route::post('/chat/message', function (Request $request) {
    $chat = Vercel::chat($request);

    return (new TodoChat())
        ->withMessages($chat->history())
        ->stream($chat)
        ->usingProtocol($chat->protocol());
});

Route::view('/chat', 'chat');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
