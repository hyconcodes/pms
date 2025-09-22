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
    public $role = 'doctor'; // Default role
    public $editMode = false;
    public $userId;
    public $showModal = false;

    protected $rules = [
        'name' => 'required|min:3|max:100',
        'email' => ['required', 'email', 'unique:users,email', 'regex:/^[a-zA-Z0-9._%+-]+@bouesti\.edu\.ng$/'],
        'password' => 'required|min:8',
        'role' => 'required|in:doctor,pharmacist'
    ];

    protected $messages = [
        'name.required' => 'Please enter the staff name! 😊',
        'email.required' => 'Email address is required! 📧',
        'email.unique' => 'This email is already registered! 🚫',
        'email.regex' => 'Email must be a valid BOUESTI email address (e.g., firstname.lastname@bouesti.edu.ng)! 📧',
        'password.required' => 'Password is required! 🔒',
        'password.min' => 'Password must be at least 8 characters! 🔐'
    ];

    public function mount() {
        $this->loadUsers();
    }

    public function loadUsers() {
        $this->users = User::whereHas('roles', function($query) {
            $query->whereNotIn('name', ['super-admin', 'patient']);
        })->latest()->get();
    }

    public function create() {
        if(!auth()->user()->can('create.staff')) {
            session()->flash('error', '🚫 You don\'t have permission for this action.');
            return;
        }

        $this->validate();

        try {
            DB::transaction(function () {
                $user = User::create([
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => Hash::make($this->password)
                ]);
                
                $user->assignRole($this->role);
            });

            $this->reset(['name', 'email', 'password', 'showModal']);
            $this->loadUsers();
            session()->flash('message', '🎉 New ' . ucfirst($this->role) . ' added successfully!');
        } catch (\Exception $e) {
            session()->flash('error', '😕 Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit($id) {
        if(!auth()->user()->can('edit.staff')) {
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

    public function update() {
        if(!auth()->user()->can('edit.staff')) {
            session()->flash('error', '🚫 You can\'t modify this record.');
            return;
        }

        $this->validate([
            'name' => 'required|min:3|max:100',
            'email' => [
                'required',
                'email',
                'unique:users,email,' . $this->userId,
                'regex:/^[a-zA-Z0-9._%+-]+@bouesti\.edu\.ng$/'
            ],
            'role' => 'required|in:doctor,pharmacist'
        ]);

        try {
            DB::transaction(function () {
                $user = User::findOrFail($this->userId);
                $updateData = [
                    'name' => $this->name,
                    'email' => $this->email,
                ];
                
                if ($this->password) {
                    $updateData['password'] = Hash::make($this->password);
                }
                
                $user->update($updateData);
                $user->syncRoles([$this->role]);
            });

            $this->reset(['name', 'email', 'password', 'editMode', 'userId', 'showModal']);
            $this->loadUsers();
            session()->flash('message', '✨ Staff info updated successfully!');
        } catch (\Exception $e) {
            session()->flash('error', '😬 Update failed: ' . $e->getMessage());
        }
    }

    public function delete($id) {
        if(!auth()->user()->can('delete.staff')) {
            session()->flash('error', '🚫 You can\'t delete records.');
            return;
        }

        try {
            User::findOrFail($id)->delete();
            $this->loadUsers();
            session()->flash('message', '🗑️ Staff record deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', '😱 Deletion failed: ' . $e->getMessage());
        }
    }

    public function cancelEdit() {
        $this->reset(['name', 'email', 'password', 'editMode', 'userId', 'showModal']);
    }
}; ?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-green-900 dark:text-green-100">Staff Management</h2>
            @can('create.staff')
            <flux:button wire:click="$set('showModal', true)" class="inline-flex items-center px-4 py-2 rounded-md shadow-sm text-sm font-medium !text-white !bg-green-700">
                Add New Staff
            </flux:button>
            @endcan
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
        <div class="border border-green-400 bg-green-400 text-white px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
        @endif
        @if (session()->has('error'))
        <div class="border border-red-400 bg-red-500 text-white px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
        @endif

        <!-- Staff Modal -->
        @if($showModal)
        <div class="fixed inset-0 bg-zinc-500 bg-opacity-75 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-medium text-zinc-900 mb-4">
                    {{ $editMode ? 'Edit Staff' : 'Add New Staff' }}
                </h3>
                <form wire:submit.prevent="{{ $editMode ? 'update' : 'create' }}">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700">Name</label>
                            <flux:input type="text" wire:model="name" class="mt-1 block w-full rounded-md"/>
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700">Email</label>
                            <flux:input type="email" wire:model="email" class="mt-1 block w-full rounded-md"/>
                            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700">Password</label>
                            <flux:input type="password" wire:model="password" class="mt-1 block w-full rounded-md"/>
                            @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700">Role</label>
                            <flux:select wire:model="role" class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm">
                                <option value="doctor">Doctor</option>
                                <option value="pharmacist">Pharmacist</option>
                            </flux:select>
                            @error('role') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end space-x-3">
                        <flux:button type="button" wire:click="cancelEdit" class="px-4 py-2 text-sm font-medium text-zinc-700 bg-zinc-100 rounded-md">
                            Cancel
                        </flux:button>
                        <flux:button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md">
                            {{ $editMode ? 'Update' : 'Create' }}
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Staff Table -->
        <div class="shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-green-700 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-green-700 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-green-700 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-green-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900">{{ $user->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900">{{ $user->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900">{{ ucfirst($user->roles->first()->name) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @can('edit.staff')
                            <flux:button wire:click="edit({{ $user->id }})" class="!text-green-600 hover:!text-green-800 mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </flux:button>
                            @endcan
                            @can('delete.staff')
                            <flux:button x-data="" x-on:click.prevent="confirm('Are you sure you want to delete this staff member?') && $wire.delete({{ $user->id }})" class="!text-red-600 hover:!text-red-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </flux:button>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
