<?php

namespace Tests\Feature\Ai\Agents;

use App\Ai\Agents\TodoStream;
use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;
use Tests\TestCase;

class TodoStreamTest extends TestCase
{
    use RefreshDatabase;

    public function test_context_includes_completed_todos_with_title_notes_and_state(): void
    {
        Todo::factory()->create([
            'title' => 'Open task',
            'description' => null,
            'created_at' => now()->subMinute(),
        ]);
        Todo::factory()->completed()->create([
            'title' => 'Купити молоко',
            'description' => 'Два літри',
            'created_at' => now(),
        ]);

        $this->assertSame([
            [
                'title' => 'Купити молоко',
                'description' => 'Два літри',
                'completed' => true,
            ],
            [
                'title' => 'Open task',
                'description' => null,
                'completed' => false,
            ],
        ], $this->messagePayloads(new TodoStream));
    }

    public function test_context_keeps_only_the_ten_newest_todos(): void
    {
        foreach (range(1, 11) as $index) {
            Todo::factory()->create([
                'title' => 'Task '.$index,
                'completed' => $index % 2 === 0,
                'created_at' => now()->addMinutes($index),
            ]);
        }

        $this->assertSame(
            array_map(fn (int $index): string => 'Task '.$index, range(11, 2)),
            array_column($this->messagePayloads(new TodoStream), 'title'),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function messagePayloads(TodoStream $agent): array
    {
        $payloads = [];

        foreach ($agent->messages() as $message) {
            $this->assertInstanceOf(Message::class, $message);
            $this->assertSame(MessageRole::User, $message->role);

            $decoded = json_decode((string) $message->content, true);

            $this->assertIsArray($decoded);

            $payloads[] = $decoded;
        }

        return $payloads;
    }
}
