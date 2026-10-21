<?php

use App\Ai\Agents\TodoStream;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::livewire('todos', 'pages::todos')->name('todos');

Route::get('stream', function () {
    return (new TodoStream)->stream('Проаналізуй завдання...');
});

Route::view('/stream-view', 'stream');

Route::livewire('chat', 'pages::chat')->name('chat');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
