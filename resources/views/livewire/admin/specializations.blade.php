<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $specializations;
    public $name;
    public $description;
    public $editMode = false;
    public $specializationId;

    protected $rules = [
        'name' => 'required|min:3|max:100',
        'description' => 'required|min:10|max:500'
    ];

    protected $messages = [
        'name.required' => 'The specialization name is required.',
        'description.required' => 'The description is required.',
        'description.min' => 'The description must be at least 10 characters.',
    ];

    public function mount()
    {
        $this->loadSpecializations();
    }

    public function loadSpecializations()
    {
        $this->specializations = DB::table('specializations')->orderBy('created_at', 'desc')->get();
    }

    public function create()
    {
        $this->validate();

        try {
            DB::table('specializations')->insert([
                'name' => $this->name,
                'description' => $this->description,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->reset(['name', 'description']);
            $this->loadSpecializations();
            session()->flash('message', 'Specialization created successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error creating specialization: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $this->editMode = true;
            $this->specializationId = $id;

            $specialization = DB::table('specializations')->where('id', $id)->first();
            $this->name = $specialization->name;
            $this->description = $specialization->description;
        } catch (\Exception $e) {
            session()->flash('error', 'Error editing specialization: ' . $e->getMessage());
            $this->cancelEdit();
        }
    }

    public function update()
    {
        $this->validate();

        try {
            DB::table('specializations')
                ->where('id', $this->specializationId)
                ->update([
                    'name' => $this->name,
                    'description' => $this->description,
                    'updated_at' => now()
                ]);

            $this->reset(['name', 'description', 'editMode', 'specializationId']);
            $this->loadSpecializations();
            session()->flash('message', 'Specialization updated successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating specialization: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            DB::table('specializations')->where('id', $id)->delete();
            $this->loadSpecializations();
            session()->flash('message', 'Specialization deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting specialization: ' . $e->getMessage());
        }
    }

    public function cancelEdit()
    {
        $this->reset(['name', 'description', 'editMode', 'specializationId']);
    }
}; ?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-green-900 dark:text-green-100">Specialization Management</h2>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="border border-green-400 bg-green-400 text-white dark:text-white px-4 py-3 rounded relative mb-4"
                role="alert">
                <span class="block sm:inline">{{ session('message') }}</span>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="border border-red-400 bg-red-500 text-white dark:text-white px-4 py-3 rounded relative mb-4"
                role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Specialization Form -->
        <div class="rounded-lg shadow-md p-6 mb-6">
            <form wire:submit.prevent="{{ $editMode ? 'update' : 'create' }}">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-green-700 dark:text-green-300">Specialization Name</label>
                        <flux:input type="text" wire:model="name"
                            class="mt-1 block w-full rounded-md text-green-700 dark:text-green-300 shadow-sm focus:border-green-500 focus:ring-green-500"/>
                        @error('name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-green-700 dark:text-green-300">Description</label>
                        <flux:textarea wire:model="description"
                            class="mt-1 block w-full rounded-md text-green-700 dark:text-green-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                            rows="3"/>
                        @error('description')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex justify-end space-x-3">
                        @if ($editMode)
                            <button type="button" wire:click="cancelEdit"
                                class="inline-flex items-center px-4 py-2 rounded-md shadow-sm text-sm font-medium text-green-700 dark:text-green-300 hover:text-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                Cancel
                            </button>
                        @endif
                        <flux:button type="submit"
                            class="inline-flex items-center px-4 py-2 rounded-md shadow-sm text-sm font-medium !text-white !bg-green-700 dark:!text-green-300 hover:!text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            {{ $editMode ? 'Update Specialization' : 'Create Specialization' }}
                        </flux:button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Specializations Table -->
        <div class="shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-green-700 dark:text-green-300 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-green-700 dark:text-green-300 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-green-700 dark:text-green-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($specializations as $specialization)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-green-700 dark:text-green-300">{{ $specialization->name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-green-700 dark:text-green-300">{{ $specialization->description }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <flux:button wire:click="edit({{ $specialization->id }})"
                                    class="!text-green-600 hover:!text-green-800 mr-3">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </flux:button>
                                <flux:button x-data=""
                                    x-on:click.prevent="if (confirm('Are you sure you want to delete this specialization?')) $wire.delete({{ $specialization->id }})"
                                    class="!text-green-600 hover:!text-green-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
