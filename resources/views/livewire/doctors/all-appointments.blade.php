<?php

use Livewire\Volt\Component;
use App\Models\MedicalRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $perPage = 10;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function viewAppointment($appointmentId)
    {
        return redirect()->route('doctor.update.appointment', $appointmentId);
    }

    public function with(): array
    {
        try {
            $query = MedicalRecord::with(['patient'])
                ->where('doctor_id', Auth::id())
                ->latest();

            if ($this->search) {
                $query->whereHas('patient', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            }

            if ($this->statusFilter) {
                $query->where('status', $this->statusFilter);
            }

            $appointments = $query->paginate($this->perPage);

            return [
                'appointments' => $appointments,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to load appointments', [
                'doctor_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Failed to load appointments.');
            
            return [
                'appointments' => collect()->paginate($this->perPage),
            ];
        }
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div
            class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-2xl shadow-xl border border-slate-200/50 dark:border-slate-700/50 overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-5 border-b border-slate-200/60 dark:border-slate-700/60">
                <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">All Appointments</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage and view your appointments</p>
            </div>

            <!-- Filters -->
            <div class="p-6 border-b border-slate-200/60 dark:border-slate-700/60">
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Search
                            Patient</label>
                        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by patient name..." />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Status</label>
                        <flux:select wire:model.live="statusFilter" placeholder="All statuses">
                            <option value="">All</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </flux:select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Per
                            Page</label>
                        <flux:select wire:model.live="perPage">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </flux:select>
                    </div>
                </div>
            </div>

            <!-- Appointments List -->
            <div class="p-6">
                @if ($appointments->count() > 0)
                    <div class="space-y-4">
                        @foreach ($appointments as $appointment)
                            <div
                                class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-4 mb-2">
                                            <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                                                {{ $appointment->patient->name }}
                                            </h3>
                                            <span
                                                class="px-2 py-1 text-xs font-medium rounded-full
                                                @if ($appointment->status === 'pending') bg-amber-100 text-amber-800 dark:bg-amber-900/20 dark:text-amber-300
                                                @elseif($appointment->status === 'completed') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300
                                                @else bg-rose-100 text-rose-800 dark:bg-rose-900/20 dark:text-rose-300 @endif">
                                                {{ ucfirst($appointment->status) }}
                                            </span>
                                        </div>
                                        <div class="grid md:grid-cols-3 gap-4 text-sm">
                                            <div>
                                                <p class="text-slate-500 dark:text-slate-400">Date & Time</p>
                                                <p class="text-slate-700 dark:text-slate-300 font-medium">
                                                    {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y') }}
                                                    at {{ $appointment->appointment_time }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-slate-500 dark:text-slate-400">Reason</p>
                                                <p class="text-slate-700 dark:text-slate-300 font-medium">
                                                    {{ $appointment->reason_for_visit ?: '—' }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-slate-500 dark:text-slate-400">Reference</p>
                                                <p class="text-slate-700 dark:text-slate-300 font-medium font-mono">
                                                    {{ $appointment->apid }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <flux:button variant="primary" size="sm"
                                            wire:click="viewAppointment({{ $appointment->id }})">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                <path fill-rule="evenodd"
                                                    d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            View
                                        </flux:button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $appointments->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-slate-800 dark:text-slate-100">No appointments found
                        </h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Try adjusting your search or filter
                            criteria.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
