<?php

use Livewire\Volt\Component;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

new class extends Component {
    public $patients;
    public $name;
    public $email;
    public $matric_no;
    public $editMode = false;
    public $patientId;
    public $showModal = false;
    public $dailyRegistrations = [];
    public $labels = [];
    public $registrationSeries = [];
    public $selectedRole;
    public $availableRoles;

    protected $rules = [
        'name' => 'required|min:3|max:100',
        'email' => 'required|email',
        'selectedRole' => 'required',
        'matric_no' => 'nullable|string|max:20',
    ];

    protected $messages = [
        'name.required' => 'Please enter the patient name! 😊',
        'email.required' => 'Email address is required! 📧',
        'email.email' => 'Please enter a valid email address! 📧',
        'selectedRole.required' => 'Please select a role! 👥',
        'matric_no.max' => 'Matric number cannot exceed 20 characters! 📚',
    ];

    public function mount()
    {
        $this->loadPatients();
        $this->loadRegistrationStats();
        $this->availableRoles = Role::all()->pluck('name');
    }

    public function loadPatients()
    {
        $this->patients = User::role('patient')->latest()->get();
    }

    public function loadRegistrationStats()
    {
        $startDate = now()->subDays(29)->startOfDay();

        $registrations = User::role('patient')->where('created_at', '>=', $startDate)->selectRaw('DATE(created_at) as date, COUNT(*) as count')->groupBy('date')->orderBy('date')->get()->keyBy('date');

        for ($i = 0; $i < 30; $i++) {
            $day = now()
                ->subDays(29 - $i)
                ->toDateString();
            $this->labels[] = $day;
            $this->registrationSeries[] = isset($registrations[$day]) ? (int) $registrations[$day]->count : 0;
        }
    }

    public function edit($id)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            session()->flash('error', '🚫 Only super admins can edit patient information.');
            return;
        }

        try {
            $this->editMode = true;
            $this->patientId = $id;
            $patient = User::findOrFail($id);
            $this->name = $patient->name;
            $this->email = $patient->email;
            $this->matric_no = $patient->matric_no;
            $this->selectedRole = $patient->roles->first()->name;
            $this->showModal = true;
        } catch (\Exception $e) {
            session()->flash('error', '😮 Error while editing: ' . $e->getMessage());
            $this->cancelEdit();
        }
    }

    public function update()
    {
        if (!auth()->user()->hasRole('super-admin')) {
            session()->flash('error', '🚫 Only super admins can modify patient records.');
            return;
        }

        $this->validate();

        try {
            $patient = User::findOrFail($this->patientId);

            // Only check matric_no uniqueness if it has been changed
            if ($this->matric_no !== $patient->matric_no && $this->matric_no) {
                $existingUser = User::where('matric_no', $this->matric_no)->where('id', '!=', $this->patientId)->first();

                if ($existingUser) {
                    session()->flash('error', '🚫 This matric number is already assigned to another patient!');
                    return;
                }
            }

            $patient->update([
                'name' => $this->name,
                'email' => $this->email,
                'matric_no' => $this->matric_no,
            ]);

            // Update role
            $patient->syncRoles([$this->selectedRole]);

            $this->reset(['name', 'email', 'matric_no', 'editMode', 'patientId', 'showModal', 'selectedRole']);
            $this->loadPatients();
            session()->flash('message', '✨ Patient info and role updated successfully!');
        } catch (\Exception $e) {
            session()->flash('error', '😬 Update failed: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            session()->flash('error', '🚫 Only super admins can delete patient records.');
            return;
        }

        try {
            User::findOrFail($id)->delete();
            $this->loadPatients();
            session()->flash('message', '🗑️ Patient record deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', '😱 Deletion failed: ' . $e->getMessage());
        }
    }

    public function cancelEdit()
    {
        $this->reset(['name', 'email', 'matric_no', 'editMode', 'patientId', 'showModal', 'selectedRole']);
    }

    public function with(): array
    {
        return [
            'labels' => $this->labels,
            'registrationSeries' => $this->registrationSeries,
        ];
    }
}; ?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-green-900 dark:text-green-100">Patient Management</h2>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="border border-green-400 bg-green-400 dark:bg-green-600 text-white px-4 py-3 rounded relative mb-4"
                role="alert">
                <span class="block sm:inline">{{ session('message') }}</span>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="border border-red-400 bg-red-500 dark:bg-red-600 text-white px-4 py-3 rounded relative mb-4"
                role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 6
                        }
                    },
                    y: {
                        beginAtZero: true,
                        precision: 0
                    }
                }
            };

            let registrationsChartInstance;

            function initChart() {
                const regCanvas = document.getElementById('registrationChart');
                if (regCanvas) {
                    const ctx = regCanvas.getContext('2d');
                    if (registrationsChartInstance) {
                        registrationsChartInstance.destroy();
                    }
                    registrationsChartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: @json($labels),
                            datasets: [{
                                label: 'Registrations',
                                data: @json($registrationSeries),
                                borderColor: '#16a34a',
                                backgroundColor: 'rgba(22,163,74,0.15)',
                                tension: 0.35,
                                fill: true,
                                pointRadius: 2
                            }]
                        },
                        options: commonOptions
                    });
                }
            }

            document.addEventListener('DOMContentLoaded', initChart);
            document.addEventListener('livewire:navigated', initChart);
        </script>

        <!-- Registration Chart -->
        <div class="bg-white dark:bg-zinc-800 p-6 rounded-lg shadow mb-6">
            <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">Patient Registrations (Last 30 Days)
            </h3>
            <div class="h-64">
                <canvas id="registrationChart"></canvas>
            </div>
        </div>

        <!-- Patient Modal -->
        @if ($showModal)
            <div class="fixed inset-0 bg-zinc-500 dark:bg-zinc-800 bg-opacity-75 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl p-6 w-full max-w-md">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-4">Edit Patient Information</h3>
                    <form wire:submit.prevent="update">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Name</label>
                                <flux:input type="text" wire:model="name"
                                    class="mt-1 block w-full rounded-md dark:bg-zinc-700 dark:text-white" />
                                @error('name')
                                    <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Email</label>
                                <flux:input type="email" wire:model="email"
                                    class="mt-1 block w-full rounded-md dark:bg-zinc-700 dark:text-white" />
                                @error('email')
                                    <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Matric
                                    No</label>
                                <flux:input type="text" wire:model="matric_no"
                                    class="mt-1 block w-full rounded-md dark:bg-zinc-700 dark:text-white" />
                                @error('matric_no')
                                    <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Role</label>
                                <select wire:model="selectedRole"
                                    class="mt-1 block w-full rounded-md dark:bg-zinc-700 dark:text-white">
                                    @foreach ($availableRoles as $role)
                                        <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                                    @endforeach
                                </select>
                                @error('selectedRole')
                                    <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="mt-5 flex justify-end space-x-3">
                            <flux:button type="button" wire:click="cancelEdit"
                                class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 rounded-md">
                                Cancel
                            </flux:button>
                            <flux:button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 dark:bg-green-700 rounded-md">
                                Update
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- Patients Table -->
        <div class="shadow-md rounded-lg overflow-hidden bg-white dark:bg-zinc-800">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-green-700 dark:text-green-400 uppercase tracking-wider">
                            Name</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-green-700 dark:text-green-400 uppercase tracking-wider">
                            Email</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-green-700 dark:text-green-400 uppercase tracking-wider">
                            Matric No</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-green-700 dark:text-green-400 uppercase tracking-wider">
                            Registration Date</th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-green-700 dark:text-green-400 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($patients as $patient)
                        <tr class="bg-white dark:bg-zinc-800">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $patient->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $patient->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $patient->matric_no ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $patient->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.medical-records', $patient->id) }}"
                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 mr-3">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </a>
                                @role('super-admin')
                                    <flux:button wire:click="edit({{ $patient->id }})"
                                        class="!text-green-600 dark:!text-green-400 hover:!text-green-800 dark:hover:!text-green-300 mr-3">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </flux:button>
                                    <flux:button x-data=""
                                        x-on:click.prevent="confirm('Are you sure you want to delete this patient?') && $wire.delete({{ $patient->id }})"
                                        class="!text-red-600 dark:!text-red-400 hover:!text-red-800 dark:hover:!text-red-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </flux:button>
                                @endrole
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
