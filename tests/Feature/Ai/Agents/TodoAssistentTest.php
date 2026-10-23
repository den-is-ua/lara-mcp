<?php

namespace Tests\Feature\Ai\Agents;

use App\Ai\Agents\TodoAssistent;
use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;
use Tests\TestCase;

class TodoAssistentTest extends TestCase
{
    use RefreshDatabase;

    public function test_context_sends_only_the_id_and_title_of_open_todos(): void
    {
        Todo::factory()->create([
            'title' => 'Купити молоко',
            'description' => 'Два літри',
            'created_at' => now(),
        ]);
        Todo::factory()->completed()->create([
            'title' => 'Готово',
            'description' => 'Вже зроблено',
            'created_at' => now()->addMinute(),
        ]);

        $open = Todo::query()->where('title', 'Купити молоко')->firstOrFail();

        $this->assertSame([
            [
                'id' => $open->id,
                'title' => 'Купити молоко',
            ],
        ], $this->messagePayloads(new TodoAssistent));
    }

    public function test_context_keeps_the_ten_newest_open_todos(): void
    {
        foreach (range(1, 12) as $index) {
            Todo::factory()->create([
                'title' => 'Open '.$index,
                'created_at' => now()->addMinutes($index),
            ]);
        }

        Todo::factory()->completed()->create([
            'title' => 'Finished',
            'created_at' => now()->addHour(),
        ]);

        $this->assertSame(
            array_map(fn (int $index): string => 'Open '.$index, range(12, 3)),
            array_column($this->messagePayloads(new TodoAssistent), 'title'),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function messagePayloads(TodoAssistent $agent): array
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
