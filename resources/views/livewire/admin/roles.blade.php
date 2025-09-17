<?php

use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $roles;
    public $permissions;
    public $name;
    public $selectedPermissions = [];
    public $editMode = false;
    public $roleId;

    protected $rules = [
        'name' => 'required|min:3|max:100',
        'selectedPermissions' => 'required|array|min:1',
    ];

    protected $messages = [
        'name.required' => 'The role name is required.',
        'selectedPermissions.required' => 'Please select at least one permission.',
        'selectedPermissions.min' => 'Please select at least one permission.',
    ];

    public function mount()
    {
        $this->loadRoles();
        $this->loadPermissions();
    }

    public function loadPermissions()
    {
        try {
            $this->permissions = Permission::all();
        } catch (\Exception $e) {
            session()->flash('error', 'Error loading permissions. Please check if permissions are properly seeded.');
            $this->permissions = collect([]);
        }
    }

    public function loadRoles()
    {
        $this->roles = Role::with('permissions')->get();
    }

    public function create()
    {
        $this->validate();

        try {
            DB::transaction(function () {
                $role = Role::create(['name' => $this->name, 'guard_name' => 'web']);
                $permissions = Permission::whereIn('id', $this->selectedPermissions)->get();
                $role->syncPermissions($permissions);
            });

            $this->reset(['name', 'selectedPermissions']);
            $this->loadRoles();
            session()->flash('message', 'Role created successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error creating role: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $this->editMode = true;
            $this->roleId = $id;

            $role = Role::findById($id, 'web');
            $this->name = $role->name;
            $this->selectedPermissions = $role->permissions->pluck('id')->toArray();
        } catch (\Exception $e) {
            session()->flash('error', 'Error editing role: ' . $e->getMessage());
            $this->cancelEdit();
        }
    }

    public function update()
    {
        $this->validate();

        try {
            DB::transaction(function () {
                $role = Role::findById($this->roleId, 'web');
                $role->update(['name' => $this->name]);
                $permissions = Permission::whereIn('id', $this->selectedPermissions)->get();
                $role->syncPermissions($permissions);
            });

            $this->reset(['name', 'selectedPermissions', 'editMode', 'roleId']);
            $this->loadRoles();
            session()->flash('message', 'Role updated successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating role: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $role = Role::findById($id, 'web');
            if ($role->name === 'super-admin') {
                session()->flash('error', 'Cannot delete the super-admin role!');
                return;
            }

            $role->delete();
            $this->loadRoles();
            session()->flash('message', 'Role deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting role: ' . $e->getMessage());
        }
    }

    public function cancelEdit()
    {
        $this->reset(['name', 'selectedPermissions', 'editMode', 'roleId']);
    }
}; ?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-green-900 dark:text-green-100">Role Management</h2>
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

        <!-- Role Form -->
        <div class="rounded-lg shadow-md p-6 mb-6">
            <form wire:submit.prevent="{{ $editMode ? 'update' : 'create' }}">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-green-700 dark:text-green-300">Role Name</label>
                        <flux:input type="text" wire:model="name"
                            class="mt-1 block w-full rounded-md text-green-700 dark:text-green-300 shadow-sm focus:border-green-500 focus:ring-green-500"/>
                        @error('name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-green-700 dark:text-green-300 mb-2">Permissions</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach ($permissions as $permission)
                                <label class="inline-flex items-center">
                                    <input type="checkbox" wire:model="selectedPermissions"
                                        value="{{ $permission->id }}"
                                        class="rounded text-green-600 focus:ring-green-500">
                                    <span class="ml-2 text-green-700 dark:text-green-300">{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedPermissions')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex justify-end space-x-3">
                        @if ($editMode)
                            <button type="button" wire:click="cancelEdit"
                                class="inline-flex items-center px-4 py-2 rounded-md shadow-sm text-sm font-medium text-green-700 dark:text-green-300 hover:text-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Cancel
                            </button>
                        @endif
                        <flux:button type="submit"
                            class="inline-flex items-center px-4 py-2 rounded-md shadow-sm text-sm font-medium !text-white !bg-green-700 dark:!text-green-300 hover:!text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            {{-- <svg class="w-2 h-2 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg> --}}
                            {{ $editMode ? 'Update Role' : 'Create Role' }}
                        </flux:button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Roles Table -->
        <div class="shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-green-700 dark:text-green-300 uppercase tracking-wider">
                            Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-green-700 dark:text-green-300 uppercase tracking-wider">
                            Permissions</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-green-700 dark:text-green-300 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-green-700 dark:text-green-300">{{ $role->name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($role->permissions as $permission)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white dark:text-white bg-green-400">
                                            {{ $permission->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <flux:button wire:click="edit({{ $role->id }})"
                                    class="!text-green-600 hover:!text-green-800 mr-3">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </flux:button>
                                @if ($role->name !== 'super-admin')
                                    <flux:button x-data=""
                                        x-on:click.prevent="if (confirm('Are you sure you want to delete this role?')) $wire.delete({{ $role->id }})"
                                        class="!text-green-600 hover:!text-green-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </flux:button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
