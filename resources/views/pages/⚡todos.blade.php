<?php

use App\Models\Todo;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Todos')] class extends Component {
    public string $title = '';

    public string $description = '';

    public ?int $editingId = null;

    public string $editingTitle = '';

    public string $editingDescription = '';

    public ?int $deletingId = null;

    public bool $showDeleteModal = false;

    /**
     * Todos ordered with open items before completed ones.
     *
     * @return Collection<int, Todo>
     */
    #[Computed]
    public function todos(): Collection
    {
        return Todo::query()
            ->orderBy('completed')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Open todos.
     *
     * @return Collection<int, Todo>
     */
    #[Computed]
    public function incompleteTodos(): Collection
    {
        return $this->todos->where('completed', false)->values();
    }

    /**
     * Completed todos.
     *
     * @return Collection<int, Todo>
     */
    #[Computed]
    public function completedTodos(): Collection
    {
        return $this->todos->where('completed', true)->values();
    }

    /**
     * The todo awaiting delete confirmation.
     */
    #[Computed]
    public function deletingTodo(): ?Todo
    {
        if ($this->deletingId === null) {
            return null;
        }

        return Todo::query()->find($this->deletingId);
    }

    /**
     * Store a new todo.
     */
    public function create(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        Todo::query()->create([
            'title' => $validated['title'],
            'description' => $this->nullableDescription($validated['description'] ?? null),
        ]);

        $this->reset('title', 'description');

        Flux::toast(variant: 'success', text: __('Todo created.'));
    }

    /**
     * Start editing a todo.
     */
    public function edit(Todo $todo): void
    {
        $this->editingId = $todo->id;
        $this->editingTitle = $todo->title;
        $this->editingDescription = $todo->description ?? '';
        $this->resetValidation();
    }

    /**
     * Discard the in-progress edit.
     */
    public function cancelEdit(): void
    {
        $this->reset('editingId', 'editingTitle', 'editingDescription');
        $this->resetValidation();
    }

    /**
     * Save the todo currently being edited.
     */
    public function update(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $validated = $this->validate([
            'editingTitle' => ['required', 'string', 'max:255'],
            'editingDescription' => ['nullable', 'string', 'max:5000'],
        ], [], [
            'editingTitle' => __('title'),
            'editingDescription' => __('notes'),
        ]);

        $todo = Todo::query()->findOrFail($this->editingId);

        $todo->update([
            'title' => $validated['editingTitle'],
            'description' => $this->nullableDescription($validated['editingDescription'] ?? null),
        ]);

        $this->cancelEdit();

        Flux::toast(variant: 'success', text: __('Todo updated.'));
    }

    /**
     * Flip a todo between open and complete.
     */
    public function toggleCompleted(Todo $todo): void
    {
        $todo->update([
            'completed' => ! $todo->completed,
        ]);
    }

    /**
     * Ask for confirmation before deleting a todo.
     */
    public function confirmDelete(Todo $todo): void
    {
        $this->deletingId = $todo->id;
        $this->showDeleteModal = true;
    }

    /**
     * Delete the todo chosen in the confirmation modal.
     */
    public function delete(): void
    {
        if ($this->deletingId === null) {
            return;
        }

        $todo = Todo::query()->findOrFail($this->deletingId);

        $todo->delete();

        if ($this->editingId === $todo->id) {
            $this->reset('editingId', 'editingTitle', 'editingDescription');
        }

        $this->reset('deletingId', 'showDeleteModal');

        Flux::toast(variant: 'success', text: __('Todo deleted.'));
    }

    /**
     * Store an empty note as null.
     */
    private function nullableDescription(?string $description): ?string
    {
        if ($description === null || $description === '') {
            return null;
        }

        return $description;
    }
}; ?>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-8">
    <div class="flex flex-col gap-2">
        <flux:heading size="xl">{{ __('Todos') }}</flux:heading>
        <flux:text>{{ __('Add a task, then edit, complete, or delete it.') }}</flux:text>
    </div>

    <flux:card class="flex flex-col gap-4 p-6">
        <flux:heading size="lg">{{ __('New todo') }}</flux:heading>

        <form wire:submit="create" class="flex flex-col gap-4">
            <flux:input wire:model="title" :label="__('Title')" type="text" required autofocus data-test="todo-title-input" />

            <flux:textarea wire:model="description" :label="__('Notes')" rows="3" data-test="todo-description-input" />

            <div class="flex items-center">
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="create" data-test="create-todo-button">
                    {{ __('Add todo') }}
                </flux:button>
            </div>
        </form>
    </flux:card>

    <section class="flex flex-col gap-3">
        <div class="flex items-center gap-2">
            <flux:heading size="lg">{{ __('To do') }}</flux:heading>
            <flux:badge color="zinc">{{ $this->incompleteTodos->count() }}</flux:badge>
        </div>

        @forelse ($this->incompleteTodos as $todo)
            <div wire:key="todo-{{ $todo->id }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" data-test="todo-{{ $todo->id }}">
                @if ($editingId === $todo->id)
                    <form wire:submit="update" class="flex flex-col gap-4">
                        <flux:input wire:model="editingTitle" :label="__('Title')" type="text" required data-test="edit-todo-title" />

                        <flux:textarea wire:model="editingDescription" :label="__('Notes')" rows="3" data-test="edit-todo-description" />

                        <div class="flex items-center gap-2">
                            <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="update" data-test="save-todo-button">
                                {{ __('Save') }}
                            </flux:button>
                            <flux:button type="button" variant="filled" wire:click="cancelEdit">
                                {{ __('Cancel') }}
                            </flux:button>
                        </div>
                    </form>
                @else
                    <div class="flex flex-col gap-3">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="flex min-w-0 flex-1 flex-col gap-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <flux:heading class="break-words">{{ $todo->title }}</flux:heading>
                                    <flux:badge color="amber">{{ __('Open') }}</flux:badge>
                                </div>
                                @if (filled($todo->description))
                                    <flux:text class="break-words">{{ $todo->description }}</flux:text>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <flux:button size="sm" icon="check" wire:click="toggleCompleted({{ $todo->id }})" data-test="toggle-todo-{{ $todo->id }}">
                                {{ __('Mark complete') }}
                            </flux:button>
                            <flux:button size="sm" icon="pencil" variant="filled" wire:click="edit({{ $todo->id }})" data-test="edit-todo-{{ $todo->id }}">
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button size="sm" icon="trash" variant="danger" wire:click="confirmDelete({{ $todo->id }})" data-test="delete-todo-{{ $todo->id }}">
                                {{ __('Delete') }}
                            </flux:button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <flux:text>{{ __('Nothing left to do.') }}</flux:text>
        @endforelse
    </section>

    <section class="flex flex-col gap-3">
        <div class="flex items-center gap-2">
            <flux:heading size="lg">{{ __('Completed') }}</flux:heading>
            <flux:badge color="green">{{ $this->completedTodos->count() }}</flux:badge>
        </div>

        @forelse ($this->completedTodos as $todo)
            <div wire:key="todo-{{ $todo->id }}" class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/60" data-test="todo-{{ $todo->id }}">
                @if ($editingId === $todo->id)
                    <form wire:submit="update" class="flex flex-col gap-4">
                        <flux:input wire:model="editingTitle" :label="__('Title')" type="text" required data-test="edit-todo-title" />

                        <flux:textarea wire:model="editingDescription" :label="__('Notes')" rows="3" data-test="edit-todo-description" />

                        <div class="flex items-center gap-2">
                            <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="update" data-test="save-todo-button">
                                {{ __('Save') }}
                            </flux:button>
                            <flux:button type="button" variant="filled" wire:click="cancelEdit">
                                {{ __('Cancel') }}
                            </flux:button>
                        </div>
                    </form>
                @else
                    <div class="flex flex-col gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading class="break-words text-zinc-500 line-through dark:text-zinc-400">{{ $todo->title }}</flux:heading>
                            <flux:badge color="green">{{ __('Done') }}</flux:badge>
                        </div>
                        @if (filled($todo->description))
                            <flux:text class="break-words text-zinc-500 line-through dark:text-zinc-400">{{ $todo->description }}</flux:text>
                        @endif

                        <div class="flex flex-wrap items-center gap-2">
                            <flux:button size="sm" icon="arrow-uturn-left" wire:click="toggleCompleted({{ $todo->id }})" data-test="toggle-todo-{{ $todo->id }}">
                                {{ __('Mark incomplete') }}
                            </flux:button>
                            <flux:button size="sm" icon="pencil" variant="filled" wire:click="edit({{ $todo->id }})" data-test="edit-todo-{{ $todo->id }}">
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button size="sm" icon="trash" variant="danger" wire:click="confirmDelete({{ $todo->id }})" data-test="delete-todo-{{ $todo->id }}">
                                {{ __('Delete') }}
                            </flux:button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <flux:text>{{ __('No completed todos yet.') }}</flux:text>
        @endforelse
    </section>

    <flux:modal wire:model="showDeleteModal" class="max-w-lg">
        <form wire:submit="delete" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete this todo?') }}</flux:heading>

                <flux:subheading>
                    @if ($this->deletingTodo)
                        {{ __('“:title” will be permanently removed.', ['title' => $this->deletingTodo->title]) }}
                    @else
                        {{ __('This permanently removes the todo.') }}
                    @endif
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit" data-test="confirm-delete-todo-button">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
