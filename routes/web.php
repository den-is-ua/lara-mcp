<?php

use Illuminate\Support\Facades\Route;
use App\Ai\Agents\TodoStream;

Route::view('/', 'welcome')->name('home');

Route::livewire('todos', 'pages::todos')->name('todos');

Route::get('stream', function () {
    return (new TodoStream)->stream('Проаналізуй завдання...')->then(function (StreamedAgentResponse $response) {
            $text = $response->text;

            logger($text);
        });;
});

Route::view('/stream-view', 'stream');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
