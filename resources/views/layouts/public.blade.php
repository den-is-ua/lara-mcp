<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <x-app-logo href="{{ route('home') }}" wire:navigate />

            <flux:navbar class="-mb-px">
                <flux:navbar.item icon="clipboard-document-list" :href="route('todos')" :current="request()->routeIs('todos')" wire:navigate>
                    {{ __('Todos') }}
                </flux:navbar.item>
                <flux:navbar.item icon="chat-bubble-left-right" :href="route('chat')" :current="request()->routeIs('chat')" wire:navigate>
                    {{ __('Chat') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            @auth
                <flux:navbar>
                    <flux:navbar.item icon="home" :href="route('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:navbar.item>
                </flux:navbar>
            @else
                <flux:navbar>
                    <flux:navbar.item :href="route('login')" wire:navigate>
                        {{ __('Log in') }}
                    </flux:navbar.item>
                </flux:navbar>
            @endauth
        </flux:header>

        <flux:main container>
            {{ $slot }}
        </flux:main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
