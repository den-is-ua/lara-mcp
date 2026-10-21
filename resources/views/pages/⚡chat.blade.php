<?php

use App\Ai\Agents\TodoChat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Str;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\UserMessage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Chat')] class extends Component {
    public string $draft = '';

    public string $channel = '';

    /**
     * The conversation shown in the chat.
     *
     * @var list<array{id: string, role: string, content: string}>
     */
    public array $messages = [];

    /**
     * Prepare a channel the page can subscribe to before the first message.
     */
    public function mount(): void
    {
        $this->channel = (string) Str::uuid();
    }

    /**
     * Broadcast the assistant reply for the current draft.
     */
    public function send(): void
    {
        $this->draft = trim($this->draft);

        $validated = $this->validate([
            'draft' => ['required', 'string', 'max:5000'],
            'channel' => ['required', 'uuid'],
        ]);

        $history = $this->history();

        $this->messages[] = [
            'id' => (string) Str::uuid(),
            'role' => 'user',
            'content' => $validated['draft'],
        ];

        $this->reset('draft');

        try {
            $response = (new TodoChat)
                ->withMessages($history)
                ->broadcastNow($validated['draft'], new Channel('chat.'.$validated['channel']));
        } catch (\Throwable $exception) {
            report($exception);

            $this->messages[] = [
                'id' => (string) Str::uuid(),
                'role' => 'assistant',
                'content' => __('The assistant could not reply.'),
            ];

            return;
        }

        $this->messages[] = [
            'id' => (string) Str::uuid(),
            'role' => 'assistant',
            'content' => $response->text ?? '',
        ];
    }

    /**
     * Earlier turns to send ahead of the new prompt.
     *
     * @return list<Message>
     */
    private function history(): array
    {
        return array_map(fn (array $message): Message => match ($message['role']) {
            'assistant' => new AssistantMessage($message['content']),
            default => new UserMessage($message['content']),
        }, $this->messages);
    }
}; ?>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-8">
    <div class="flex flex-col gap-2">
        <flux:heading size="xl">{{ __('Chat') }}</flux:heading>
        <flux:text>{{ __('Ask the assistant about your todos.') }}</flux:text>
    </div>

    <flux:card class="flex flex-col gap-4 p-6">
        <div data-chat-log class="flex max-h-[32rem] min-h-64 flex-col gap-3 overflow-y-auto">
            @forelse ($messages as $message)
                <div
                    wire:key="message-{{ $message['id'] }}"
                    @class([
                        'max-w-[85%] whitespace-pre-wrap rounded-lg px-4 py-3',
                        'self-end bg-zinc-200 text-zinc-900 dark:bg-zinc-700 dark:text-zinc-100' => $message['role'] === 'user',
                        'self-start border border-zinc-200 bg-white text-zinc-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100' => $message['role'] === 'assistant',
                    ])
                >{{ $message['content'] }}</div>
            @empty
                <flux:text>{{ __('Ask about your todos.') }}</flux:text>
            @endforelse

            <div data-chat-pending class="hidden max-w-[85%] self-end whitespace-pre-wrap rounded-lg bg-zinc-200 px-4 py-3 text-zinc-900 dark:bg-zinc-700 dark:text-zinc-100"></div>
            <div data-chat-stream class="hidden max-w-[85%] self-start whitespace-pre-wrap rounded-lg border border-zinc-200 bg-white px-4 py-3 text-zinc-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"></div>
        </div>

        <flux:text data-chat-status class="hidden" data-disconnected="{{ __('Broadcasting is not connected.') }}"></flux:text>

        <form wire:submit="send" class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="min-w-0 flex-1">
                <flux:input
                    wire:model="draft"
                    :label="__('Message')"
                    type="text"
                    :placeholder="__('Write a message...')"
                    required
                    autofocus
                    data-chat-input
                    data-test="chat-message-input"
                    wire:loading.attr="disabled"
                    wire:target="send"
                />
            </div>

            <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="send" data-test="send-chat-button">
                {{ __('Send') }}
            </flux:button>
        </form>
    </flux:card>
</div>

<script>
    const root = $wire.$el;
    const log = root.querySelector('[data-chat-log]');
    const pending = root.querySelector('[data-chat-pending]');
    const stream = root.querySelector('[data-chat-stream]');
    const status = root.querySelector('[data-chat-status]');
    const componentId = $wire.$id;
    const channelName = `chat.${$wire.channel}`;
    let assistantText = '';

    const show = (element, text) => {
        element.textContent = text;
        element.classList.toggle('hidden', text === '');
    };

    const scrollLog = () => {
        log.scrollTop = log.scrollHeight;
    };

    root.querySelector('form').addEventListener('submit', () => {
        const value = root.querySelector('[data-chat-input]')?.value?.trim() ?? '';

        assistantText = '';
        show(stream, '');
        show(pending, value);
        scrollLog();
    });

    if (!window.Echo) {
        status.textContent = status.dataset.disconnected;
        status.classList.remove('hidden');
    } else {
        window.Echo.channel(channelName).listen('.text_delta', (payload) => {
            assistantText += payload?.delta ?? '';
            show(stream, assistantText);
            scrollLog();
        });

        document.addEventListener('livewire:navigating', () => {
            window.Echo.leave(channelName);
        }, { once: true });
    }

    Livewire.hook('commit', ({ component, succeed }) => {
        if (component.id !== componentId) {
            return;
        }

        succeed(() => {
            assistantText = '';
            show(stream, '');
            show(pending, '');
        });
    });
</script>
