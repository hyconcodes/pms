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
    public $perPage = 8;

    // Modal state
    public $showModal = false;
    public $selectedAppointmentId;
    public $payment_amount;
    public $payment_method;
    public $payment_status = 'completed';

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

    public function openBillingModal($appointmentId)
    {
        $appointment = MedicalRecord::findOrFail($appointmentId);
        $this->selectedAppointmentId = $appointmentId;
        $this->payment_amount = $appointment->payment_amount;
        $this->payment_method = $appointment->payment_method;
        $this->showModal = true;
    }

    public function updateBilling()
    {
        $this->validate([
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,card,bank_transfer,other',
        ]);

        try {
            $appointment = MedicalRecord::findOrFail($this->selectedAppointmentId);
            $appointment->update([
                'payment_amount' => $this->payment_amount,
                'payment_method' => $this->payment_method,
                'status' => $this->payment_status,
            ]);
            session()->flash('success', 'Billing updated successfully.');
            $this->showModal = false;
        } catch (\Exception $e) {
            Log::error('Failed to update billing', [
                'appointment_id' => $this->selectedAppointmentId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to update billing.');
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['selectedAppointmentId', 'payment_amount', 'payment_method', 'payment_status']);
    }

    public function with(): array
    {
        try {
            $query = MedicalRecord::with(['patient'])
                ->latest();

            if ($this->search) {
                $query->where(function ($q) {
                    $q->whereHas('patient', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhere('apid', 'like', '%' . $this->search . '%');
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

<div class="min-h-screen bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-900 dark:to-zinc-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div
            class="bg-white/70 dark:bg-zinc-800/70 backdrop-blur-xl rounded-2xl shadow-xl border border-zinc-200/50 dark:border-zinc-700/50 overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-5 border-b border-zinc-200/60 dark:border-zinc-700/60">
                <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">All Consultation - Billing</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Manage billing and payments for all appointments</p>
            </div>

            <!-- Filters -->
            <div class="p-6 border-b border-zinc-200/60 dark:border-zinc-700/60">
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Search
                            Patient</label>
                        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by patient name..." />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Status</label>
                        <flux:select wire:model.live="statusFilter" placeholder="All statuses">
                            <option value="">All</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </flux:select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Per
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
                                class="bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-4 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-4 mb-2">
                                            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">
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
                                        <div class="grid md:grid-cols-4 gap-4 text-sm">
                                            <div>
                                                <p class="text-zinc-500 dark:text-zinc-400">Date & Time</p>
                                                <p class="text-zinc-700 dark:text-zinc-300 font-medium">
                                                    {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y') }}
                                                    at {{ $appointment->appointment_time }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-zinc-500 dark:text-zinc-400">Reference</p>
                                                <p class="text-zinc-700 dark:text-zinc-300 font-medium font-mono">
                                                    {{ $appointment->apid }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-zinc-500 dark:text-zinc-400">Amount</p>
                                                <p class="text-zinc-700 dark:text-zinc-300 font-medium">
                                                    ₦{{ number_format($appointment->payment_amount ?: 0, 2) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <flux:button class='!bg-green-600 !text-white' variant="primary"
                                            wire:click="openBillingModal({{ $appointment->id }})">
                                            Process Billing
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
                        <svg class="mx-auto h-12 w-12 text-zinc-400 dark:text-zinc-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-zinc-800 dark:text-zinc-100">No appointments found
                        </h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Try adjusting your search or filter
                            criteria.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Billing Modal -->
    <flux:modal wire:model="showModal">
        <div class="p-2">
            <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Update Billing</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Payment Amount</label>
                    <flux:input type="number" step="0.01" wire:model="payment_amount" placeholder="0.00" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Payment Method</label>
                    <flux:select wire:model="payment_method" placeholder="Select method">
                        <option value="">Select method</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="other">Other</option>
                    </flux:select>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <flux:button variant="ghost" wire:click="closeModal">Cancel</flux:button>
                <flux:button variant="primary" wire:click="updateBilling">Save Changes</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
