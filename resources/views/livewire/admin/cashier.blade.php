<?php

use Livewire\Volt\Component;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

new class extends Component {
    public $users;
    public $name;
    public $email;
    public $password;
    public $role = 'cashier'; // Fixed role
    public $editMode = false;
    public $userId;
    public $showModal = false;

    protected $rules = [
        'name' => 'required|min:3|max:100',
        'email' => ['required', 'email', 'unique:users,email', 'regex:/^[a-zA-Z0-9._%+-]+@bouesti\.edu\.ng$/'],
        'password' => 'required|min:8',
        'role' => 'required|in:cashier'
    ];

    protected $messages = [
        'name.required' => 'Please enter the staff name! 😊',
        'email.required' => 'Email address is required! 📧',
        'email.unique' => 'This email is already registered! 🚫',
        'email.regex' => 'Email must be a valid BOUESTI email address (e.g., firstname.lastname@bouesti.edu.ng)! 📧',
        'password.required' => 'Password is required! 🔒',
        'password.min' => 'Password must be at least 8 characters! 🔐',
    ];

    public function mount()
    {
        $this->loadUsers();
    }

    public function loadUsers()
    {
        $this->users = User::role('cashier')->latest()->get();
    }

    public function create()
    {
        if (!auth()->user()->can('create.staff')) {
            session()->flash('error', '🚫 You don\'t have permission for this action.');
            return;
        }

        $this->validate();

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password)
            ]);

            $user->assignRole($this->role);

            DB::commit();

            $this->reset(['name', 'email', 'password']);
            $this->showModal = false;
            $this->loadUsers();
            session()->flash('message', '✅ Cashier created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', '😱 Error creating cashier: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        if (!auth()->user()->can('edit.staff')) {
            session()->flash('error', '🚫 You don\'t have edit permissions.');
            return;
        }

        try {
            $this->editMode = true;
            $this->userId = $id;
            $user = User::findOrFail($id);
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->roles->first()->name;
            $this->showModal = true;
        } catch (\Exception $e) {
            session()->flash('error', '😮 Error while editing: ' . $e->getMessage());
            $this->cancelEdit();
        }
    }

    public function update()
    {
        if (!auth()->user()->can('edit.staff')) {
            session()->flash('error', '🚫 You can\'t modify this record.');
            return;
        }

        $rules = [
            'name' => 'required|min:3|max:100',
            'email' => ['required', 'email', 'unique:users,email,' . $this->userId, 'regex:/^[a-zA-Z0-9._%+-]+@bouesti\\.edu\\.ng$/'],
            'role' => 'required|in:cashier'
        ];

        if ($this->password) {
            $rules['password'] = 'min:8';
        }

        $this->validate($rules);

        try {
            DB::beginTransaction();

            $user = User::findOrFail($this->userId);
            $user->name = $this->name;
            $user->email = $this->email;

            if ($this->password) {
                $user->password = Hash::make($this->password);
            }

            $user->save();
            $user->syncRoles([$this->role]);

            DB::commit();

            $this->reset(['name', 'email', 'password', 'userId', 'editMode', 'showModal']);
            $this->loadUsers();
            session()->flash('message', '✅ Cashier updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', '😱 Error updating cashier: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!auth()->user()->can('delete.staff')) {
            session()->flash('error', '🚫 You don\'t have permission to delete staff.');
            return;
        }

        try {
            $user = User::findOrFail($id);
            $user->delete();

            $this->loadUsers();
            session()->flash('message', '🗑️ Cashier deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', '😱 Deletion failed: ' . $e->getMessage());
        }
    }

    public function cancelEdit()
    {
        $this->reset(['name', 'email', 'password', 'editMode', 'userId', 'showModal']);
    }
}; ?>

<main class="min-h-screen bg-zinc-50 dark:bg-zinc-900 py-8 px-3 sm:px-6 lg:px-8 rounded">
    <div class="max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-green-800 dark:text-green-100">Cashier Management</h1>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                    Manage cashiers & their accounts easily.
                </p>
            </div>
            @can('create.staff')
                <flux:button wire:click="$set('showModal', true)"
                    class="mt-4 sm:mt-0 inline-flex items-center gap-2 px-4 py-2 rounded-lg shadow-sm text-sm font-medium text-white bg-green-700 hover:bg-green-800 focus:ring-2 focus:ring-offset-2 focus:ring-green-600">
                    Add Cashier
                </flux:button>
            @endcan
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="mb-6 rounded-lg bg-green-600 text-white px-4 py-3 shadow-md flex items-center justify-between">
                <span>{{ session('message') }}</span>
                <button class="ml-3 text-white hover:text-zinc-200" onclick="this.parentElement.remove()">✕</button>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="mb-6 rounded-lg bg-red-600 text-white px-4 py-3 shadow-md flex items-center justify-between">
                <span>{{ session('error') }}</span>
                <button class="ml-3 text-white hover:text-zinc-200" onclick="this.parentElement.remove()">✕</button>
            </div>
        @endif

        <!-- Cashiers Table -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-green-50 dark:bg-green-900/40">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-green-700 uppercase tracking-wide">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-green-700 uppercase tracking-wide">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-green-700 uppercase tracking-wide">Account Type</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-green-700 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @forelse ($users as $user)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/40 transition">
                                <td class="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $user->name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-700 dark:text-zinc-300">
                                    {{ $user->email }}
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-700 dark:text-zinc-300">
                                    Cashier
                                </td>
                                <td class="px-6 py-4 text-right text-sm space-x-2">
                                    @can('edit.staff')
                                        <flux:button wire:click="edit({{ $user->id }})"
                                            class="text-green-600 hover:text-green-800">
                                            ✎
                                        </flux:button>
                                    @endcan
                                    @can('delete.staff')
                                        <flux:button
                                            x-on:click.prevent="confirm('Are you sure you want to delete this cashier?') && $wire.delete({{ $user->id }})"
                                            class="text-red-600 hover:text-red-800">
                                            🗑
                                        </flux:button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-6 text-center text-zinc-500 dark:text-zinc-400 text-sm">
                                    No cashiers found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Cashier Modal -->
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="w-full max-w-xl bg-white dark:bg-zinc-800 rounded-xl shadow-xl overflow-hidden relative p-6">
                <button type="button" wire:click="cancelEdit"
                    class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600">
                    ✕
                </button>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100 mb-6">
                    {{ $editMode ? 'Edit Cashier' : 'Add New Cashier' }}
                </h3>

                <form wire:submit.prevent="{{ $editMode ? 'update' : 'create' }}" class="space-y-5">
                    <div class="space-y-5">
                        <div>
                            <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Name</label>
                            <flux:input wire:model="name" id="name" class="w-full mt-1" type="text" required />
                            @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Email</label>
                            <flux:input wire:model="email" id="email" class="w-full mt-1" type="email" required />
                            @error('email') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ $editMode ? 'New Password (optional)' : 'Password' }}
                            </label>
                            <flux:input wire:model="password" id="password" class="w-full mt-1" type="password"
                                :required="!$editMode" />
                            @error('password') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="role" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Role</label>
                            <flux:input wire:model="role" id="role" class="w-full mt-1 bg-zinc-100 dark:bg-zinc-700" type="text"
                                readonly required />
                            @error('role') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" wire:click="cancelEdit"
                            class="px-4 py-2 text-sm font-medium text-zinc-700 bg-zinc-100 rounded-lg hover:bg-zinc-200">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                            {{ $editMode ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</main>
