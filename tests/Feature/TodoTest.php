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
            ->call('create')
            ->assertHasNoErrors();

        $todo = Todo::query()->where('title', 'Write tests')->first();

        $this->assertNotNull($todo);
        $this->assertModelExists($todo);
        $this->assertSame('Write tests', $todo->title);
        $this->assertSame('Cover create', $todo->description);
        $this->assertFalse($todo->completed);
    }

    public function test_open_todos_are_shown_newest_first_before_completed_ones(): void
    {
        Todo::factory()->create(['title' => 'Older open']);
        Todo::factory()->completed()->create(['title' => 'Finished first']);
        Todo::factory()->create(['title' => 'Newer open']);
        Todo::factory()->completed()->create(['title' => 'Finished later']);

        $this->get(route('todos'))
            ->assertOk()
            ->assertSeeInOrder([
                'To do',
                'Newer open',
                'Older open',
                'Completed',
                'Finished later',
                'Finished first',
            ]);
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

    public function test_title_longer_than_255_characters_is_rejected_when_creating_a_todo(): void
    {
        Livewire::test('pages::todos')
            ->set('title', str_repeat('a', 256))
            ->set('description', 'Notes')
            ->call('create')
            ->assertHasErrors(['title' => 'The title field must not be greater than 255 characters.']);

        $this->assertDatabaseCount('todos', 0);
    }

    public function test_notes_longer_than_5000_characters_are_rejected_when_creating_a_todo(): void
    {
        Livewire::test('pages::todos')
            ->set('title', 'Write tests')
            ->set('description', str_repeat('a', 5001))
            ->call('create')
            ->assertHasErrors(['description' => 'The description field must not be greater than 5000 characters.']);

        $this->assertDatabaseCount('todos', 0);
    }

    public function test_empty_notes_are_stored_as_null_when_creating_a_todo(): void
    {
        Livewire::test('pages::todos')
            ->set('title', 'Write tests')
            ->set('description', '')
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('title', '')
            ->assertSet('description', '');

        $todo = Todo::query()->where('title', 'Write tests')->first();

        $this->assertNotNull($todo);
        $this->assertNull($todo->description);
        $this->assertFalse($todo->completed);
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
            ->call('update')
            ->assertHasNoErrors();

        $todo->refresh();

        $this->assertSame('New title', $todo->title);
        $this->assertSame('New notes', $todo->description);
        $this->assertFalse($todo->completed);
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

    public function test_title_longer_than_255_characters_is_rejected_when_updating_a_todo(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Keep me',
            'description' => 'Same notes',
        ]);

        Livewire::test('pages::todos')
            ->call('edit', $todo->id)
            ->set('editingTitle', str_repeat('a', 256))
            ->call('update')
            ->assertHasErrors(['editingTitle' => 'The title field must not be greater than 255 characters.']);

        $todo->refresh();

        $this->assertSame('Keep me', $todo->title);
        $this->assertSame('Same notes', $todo->description);
    }

    public function test_notes_longer_than_5000_characters_are_rejected_when_updating_a_todo(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Keep me',
            'description' => 'Same notes',
        ]);

        Livewire::test('pages::todos')
            ->call('edit', $todo->id)
            ->set('editingDescription', str_repeat('a', 5001))
            ->call('update')
            ->assertHasErrors(['editingDescription' => 'The notes field must not be greater than 5000 characters.']);

        $todo->refresh();

        $this->assertSame('Keep me', $todo->title);
        $this->assertSame('Same notes', $todo->description);
    }

    public function test_clearing_notes_stores_null(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Keep me',
            'description' => 'Old notes',
        ]);

        Livewire::test('pages::todos')
            ->call('edit', $todo->id)
            ->set('editingDescription', '')
            ->call('update')
            ->assertHasNoErrors()
            ->assertSet('editingId', null);

        $todo->refresh();

        $this->assertSame('Keep me', $todo->title);
        $this->assertNull($todo->description);
    }

    public function test_canceling_an_edit_leaves_the_todo_unchanged(): void
    {
        $todo = Todo::factory()->create([
            'title' => 'Keep me',
            'description' => 'Same notes',
        ]);

        Livewire::test('pages::todos')
            ->call('edit', $todo->id)
            ->set('editingTitle', 'Changed')
            ->set('editingDescription', 'Changed notes')
            ->call('cancelEdit')
            ->assertSet('editingId', null)
            ->assertSet('editingTitle', '')
            ->assertSet('editingDescription', '');

        $todo->refresh();

        $this->assertSame('Keep me', $todo->title);
        $this->assertSame('Same notes', $todo->description);
    }

    public function test_deleting_the_todo_being_edited_clears_the_editor(): void
    {
        $todo = Todo::factory()->create(['title' => 'Remove me']);

        Livewire::test('pages::todos')
            ->call('edit', $todo->id)
            ->set('editingTitle', 'Unsaved change')
            ->call('confirmDelete', $todo->id)
            ->call('delete')
            ->assertHasNoErrors()
            ->assertSet('editingId', null)
            ->assertSet('editingTitle', '')
            ->assertSet('editingDescription', '')
            ->assertSet('showDeleteModal', false);

        $this->assertModelMissing($todo);
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
}
