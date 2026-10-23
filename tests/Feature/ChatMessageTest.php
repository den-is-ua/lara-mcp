<?php

namespace Tests\Feature;

use App\Ai\Agents\TodoChat;
use Illuminate\Broadcasting\AnonymousEvent;
use Illuminate\Support\Facades\Event;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Prompts\AgentPrompt;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class ChatMessageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_can_view_the_chat_page(): void
    {
        $response = $this->get(route('chat'));

        $response->assertOk();
        $response->assertSee('Chat');
        $response->assertSee('Write a message...');
        $response->assertSee('data-chat-stream', false);
    }

    public function test_surrounding_whitespace_is_trimmed_before_the_assistant_is_prompted(): void
    {
        TodoChat::fake(['Noted']);
        Event::fake([AnonymousEvent::class]);

        Livewire::test('pages::chat')
            ->set('draft', '  Hello  ')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('draft', '')
            ->assertSet('messages.0.content', 'Hello')
            ->assertSet('messages.1.role', 'assistant')
            ->assertSet('messages.1.content', 'Noted');

        TodoChat::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->prompt === 'Hello');
    }

    public function test_unknown_history_roles_are_sent_as_user_messages(): void
    {
        TodoChat::fake(['Ok']);
        Event::fake([AnonymousEvent::class]);

        Livewire::test('pages::chat')
            ->set('messages', [
                ['id' => '1', 'role' => 'system', 'content' => 'Hidden context'],
            ])
            ->set('draft', 'Hello')
            ->call('send')
            ->assertHasNoErrors();

        TodoChat::assertPrompted(function (AgentPrompt $prompt): bool {
            return $prompt->prompt === 'Hello'
                && count($prompt->messages ?? []) === 1
                && $prompt->messages[0] instanceof UserMessage
                && $prompt->messages[0]->content === 'Hidden context';
        });
    }

    public function test_whitespace_message_is_rejected(): void
    {
        TodoChat::fake();

        Livewire::test('pages::chat')
            ->set('draft', '   ')
            ->call('send')
            ->assertHasErrors(['draft' => 'The draft field is required.'])
            ->assertSet('messages', []);

        TodoChat::assertNeverPrompted();
    }

    public function test_message_longer_than_5000_characters_is_rejected(): void
    {
        TodoChat::fake();

        Livewire::test('pages::chat')
            ->set('draft', str_repeat('a', 5001))
            ->call('send')
            ->assertHasErrors(['draft' => 'The draft field must not be greater than 5000 characters.'])
            ->assertSet('messages', []);

        TodoChat::assertNeverPrompted();
    }

    public function test_invalid_channel_does_not_prompt_the_assistant(): void
    {
        TodoChat::fake();

        Livewire::test('pages::chat')
            ->set('channel', 'orders')
            ->set('draft', 'Hello')
            ->call('send')
            ->assertHasErrors(['channel' => 'The channel field must be a valid UUID.'])
            ->assertSet('messages', []);

        TodoChat::assertNeverPrompted();
    }

    public function test_sending_a_message_broadcasts_the_reply_and_keeps_the_conversation(): void
    {
        TodoChat::fake(['All done']);
        Event::fake([AnonymousEvent::class]);

        $channel = '550e8400-e29b-41d4-a716-446655440000';

        Livewire::test('pages::chat')
            ->set('channel', $channel)
            ->set('messages', [
                ['id' => '1', 'role' => 'user', 'content' => 'Remember this'],
                ['id' => '2', 'role' => 'assistant', 'content' => 'Noted'],
            ])
            ->set('draft', 'What did I say?')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('draft', '')
            ->assertCount('messages', 4)
            ->assertSet('messages.2.role', 'user')
            ->assertSet('messages.2.content', 'What did I say?')
            ->assertSet('messages.3.role', 'assistant')
            ->assertSet('messages.3.content', 'All done');

        TodoChat::assertPrompted(function (AgentPrompt $prompt): bool {
            return $prompt->prompt === 'What did I say?'
                && count($prompt->messages ?? []) === 2
                && $prompt->messages[0] instanceof UserMessage
                && $prompt->messages[0]->content === 'Remember this'
                && $prompt->messages[1] instanceof AssistantMessage
                && $prompt->messages[1]->content === 'Noted';
        });

        $deltas = Event::dispatched(AnonymousEvent::class)
            ->filter(function (array $arguments) use ($channel): bool {
                $event = $arguments[0];

                return $event instanceof AnonymousEvent
                    && $event->broadcastAs() === 'text_delta'
                    && $this->broadcastChannelName($event) === 'chat.'.$channel;
            })
            ->map(fn (array $arguments): string => $arguments[0]->broadcastWith()['delta']);

        $this->assertSame('All done', $deltas->implode(''));
        Event::assertDispatched(AnonymousEvent::class, function (AnonymousEvent $event) use ($channel): bool {
            return $event->broadcastAs() === 'stream_end'
                && $this->broadcastChannelName($event) === 'chat.'.$channel;
        });
    }

    public function test_a_failed_reply_is_stored_in_the_conversation(): void
    {
        TodoChat::fake(function (): void {
            throw new RuntimeException('The provider failed.');
        });

        Livewire::test('pages::chat')
            ->set('draft', 'Hello')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('draft', '')
            ->assertCount('messages', 2)
            ->assertSet('messages.0.role', 'user')
            ->assertSet('messages.0.content', 'Hello')
            ->assertSet('messages.1.role', 'assistant')
            ->assertSet('messages.1.content', 'The assistant could not reply.');
    }

    private function broadcastChannelName(AnonymousEvent $event): string
    {
        $channels = $event->broadcastOn();
        $channel = is_array($channels) ? $channels[0] : $channels;

        return (string) $channel;
    }
}
