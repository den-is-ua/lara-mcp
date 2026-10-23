<?php

namespace Tests\Feature;

use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TodoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_can_view_open_and_completed_todos(): void
    {
        Todo::factory()->create(['title' => 'Buy milk']);
        Todo::factory()->completed()->create(['title' => 'Walk the dog']);

        $response = $this->get(route('todos'));

        $response->assertOk();
        $response->assertSeeInOrder(['To do', 'Buy milk', 'Completed', 'Walk the dog']);
    }

    public function test_creating_a_todo_stores_it(): void
    {
        Livewire::test('pages::todos')
            ->set('title', 'Write tests')
            ->set('description', 'Cover create')
            ->set('labels', ['backend', 'urgent'])
            ->call('create')
            ->assertHasNoErrors();

        $todo = Todo::query()->where('title', 'Write tests')->first();

        $this->assertNotNull($todo);
        $this->assertModelExists($todo);
        $this->assertSame('Write tests', $todo->title);
        $this->assertSame('Cover create', $todo->description);
        $this->assertSame(['backend', 'urgent'], $todo->label);
        $this->assertNull($todo->due_date);
        $this->assertFalse($todo->completed);
    }

    public function test_empty_title_is_rejected_when_creating_a_todo(): void
    {
        Livewire::test('pages::todos')
            ->set('title', '')
            ->set('description', 'Notes without a title')
            ->call('create')
            ->assertHasErrors(['title' => 'The title field is required.']);

        $this->assertDatabaseCount('todos', 0);
    }

    public function test_creating_a_todo_stores_the_due_date(): void
    {
        Livewire::test('pages::todos')
            ->set('title', 'Write tests')
            ->set('dueDate', '2026-10-30')
            ->call('create')
            ->assertHasNoErrors();

        $todo = Todo::query()->where('title', 'Write tests')->first();

        $this->assertNotNull($todo);
        $this->assertSame('2026-10-30', $todo->due_date?->toDateString());
    }

    public function test_invalid_due_date_is_rejected_when_creating_a_todo(): void
    {
        Livewire::test('pages::todos')
            ->set('title', 'Write tests')
            ->set('dueDate', 'tomorrow')
            ->call('create')
            ->assertHasErrors(['dueDate' => 'The due date field must be a valid date.']);

        $this->assertDatabaseCount('todos', 0);
    }

    public function test_updating_a_todo_changes_its_title_and_notes(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Old title',
            'description' => 'Old notes',
        ]);

        Livewire::test('pages::todos')
            ->call('edit', $todo->id)
            ->set('editingTitle', 'New title')
            ->set('editingDescription', 'New notes')
            ->set('editingLabels', ['frontend', 'review'])
            ->call('update')
            ->assertHasNoErrors();

        $todo->refresh();

        $this->assertSame('New title', $todo->title);
        $this->assertSame('New notes', $todo->description);
        $this->assertSame(['frontend', 'review'], $todo->label);
        $this->assertNull($todo->due_date);
        $this->assertFalse($todo->completed);
    }

    public function test_updating_a_todo_stores_the_due_date(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Write tests',
            'due_date' => '2026-10-30',
        ]);

        Livewire::test('pages::todos')
            ->call('edit', $todo->id)
            ->assertSet('editingDueDate', '2026-10-30')
            ->set('editingDueDate', '2026-11-02')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('2026-11-02', $todo->refresh()->due_date?->toDateString());
    }

    public function test_clearing_the_due_date_stores_null(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Write tests',
            'due_date' => '2026-10-30',
        ]);

        Livewire::test('pages::todos')
            ->call('edit', $todo->id)
            ->set('editingDueDate', '')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertNull($todo->refresh()->due_date);
    }

    public function test_empty_title_is_rejected_when_updating_a_todo(): void
    {
        $todo = Todo::factory()->create(['title' => 'Keep me']);

        Livewire::test('pages::todos')
            ->call('edit', $todo->id)
            ->set('editingTitle', '')
            ->call('update')
            ->assertHasErrors(['editingTitle' => 'The title field is required.']);

        $this->assertSame('Keep me', $todo->refresh()->title);
    }

    public function test_toggling_a_todo_marks_it_complete(): void
    {
        $todo = Todo::factory()->create();

        Livewire::test('pages::todos')
            ->call('toggleCompleted', $todo->id)
            ->assertHasNoErrors();

        $this->assertTrue($todo->refresh()->completed);
    }

    public function test_toggling_a_completed_todo_marks_it_incomplete(): void
    {
        $todo = Todo::factory()->completed()->create();

        Livewire::test('pages::todos')
            ->call('toggleCompleted', $todo->id)
            ->assertHasNoErrors();

        $this->assertFalse($todo->refresh()->completed);
    }

    public function test_deleting_a_todo_removes_it(): void
    {
        $todo = Todo::factory()->create();

        Livewire::test('pages::todos')
            ->call('confirmDelete', $todo->id)
            ->assertSet('showDeleteModal', true)
            ->call('delete')
            ->assertHasNoErrors()
            ->assertSet('showDeleteModal', false);

        $this->assertModelMissing($todo);
    }

    public function test_todo_title_is_escaped_when_rendered(): void
    {
        Todo::factory()->create(['title' => '<script>alert("x")</script>']);

        $response = $this->get(route('todos'));

        $response->assertOk();
        $response->assertSee('<script>alert("x")</script>');
        $response->assertDontSee('<script>alert("x")</script>', false);
    }

    public function test_labels_are_rendered_in_the_todo_list(): void
    {
        Todo::factory()->create([
            'title' => 'Plan release',
            'label' => ['backend', 'urgent'],
        ]);

        $response = $this->get(route('todos'));

        $response->assertOk();
        $response->assertSee('backend');
        $response->assertSee('urgent');
    }

    public function test_due_date_is_rendered_in_the_todo_list(): void
    {
        Todo::factory()->create([
            'title' => 'Plan release',
            'due_date' => '2026-10-30',
        ]);

        $response = $this->get(route('todos'));

        $response->assertOk();
        $response->assertSee('2026-10-30');
    }
}
