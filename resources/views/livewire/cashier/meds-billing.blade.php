<?php

use Livewire\Volt\Component;
use App\Models\Prescription;
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
    public $selectedPrescriptionId;
    public $payment_amount;
    public $payment_method;
    public $payment_status = 'pending';

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

    public function openBillingModal($prescriptionId)
    {
        $prescription = Prescription::findOrFail($prescriptionId);
        $this->selectedPrescriptionId = $prescriptionId;
        $this->payment_amount = $prescription->payment_amount;
        $this->payment_method = $prescription->payment_method;
        $this->payment_status = $prescription->payment_status;
        $this->showModal = true;
    }

    public function updateBilling()
    {
        $this->validate([
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,card,bank_transfer,other',
            'payment_status' => 'required|in:pending,paid,failed,refunded',
        ]);

        try {
            $prescription = Prescription::findOrFail($this->selectedPrescriptionId);
            $prescription->update([
                'payment_amount' => $this->payment_amount,
                'payment_method' => $this->payment_method,
                'payment_status' => $this->payment_status,
            ]);
            session()->flash('success', 'Billing updated successfully.');
            $this->showModal = false;
        } catch (\Exception $e) {
            Log::error('Failed to update billing', [
                'prescription_id' => $this->selectedPrescriptionId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to update billing.');
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['selectedPrescriptionId', 'payment_amount', 'payment_method', 'payment_status']);
    }

    public function with(): array
    {
        try {
            // Eager-load relationships to show names instead of IDs
            $query = Prescription::with(['appointment.patient', 'appointment.doctor', 'medication'])
                                 ->latest();

            if ($this->search) {
                $query->where(function ($q) {
                    // Search by prescription id
                    $q->where('id', 'like', '%' . $this->search . '%');
                });
            }

            if ($this->statusFilter) {
                $query->where('payment_status', $this->statusFilter);
            }

            $prescriptions = $query->paginate($this->perPage);

            return [
                'prescriptions' => $prescriptions,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to load prescriptions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Failed to load prescriptions.');

            return [
                'prescriptions' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage, $this->getPage(), ['path' => request()->url()]),
            ];
        }
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-900 dark:to-zinc-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
        <div class="bg-white/80 dark:bg-zinc-800/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-zinc-200/60 dark:border-zinc-700/60 overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-6 md:px-8 md:py-8 border-b border-zinc-200/60 dark:border-zinc-700/60">
                <h1 class="text-2xl md:text-3xl font-extrabold text-zinc-800 dark:text-zinc-100 tracking-tight">Prescription Medication – Billing</h1>
                <p class="text-sm md:text-base text-zinc-500 dark:text-zinc-400 mt-2">Manage billing and payments for prescription medications</p>
            </div>

            <!-- Filters -->
            <div class="p-6 md:p-8 border-b border-zinc-200/60 dark:border-zinc-700/60">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Search Prescription ID</label>
                        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by prescription ID…" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Payment Status</label>
                        <flux:select wire:model.live="statusFilter" placeholder="All statuses">
                            <option value="">All</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </flux:select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Per Page</label>
                        <flux:select wire:model.live="perPage">
                            <option value="8">8</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </flux:select>
                    </div>
                </div>
            </div>

            <!-- Prescriptions List -->
            <div class="p-6 md:p-8">
                @if ($prescriptions->count())
                    <div class="grid gap-4 md:gap-6">
                        @foreach ($prescriptions as $prescription)
                            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-2xl p-5 md:p-6 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-all duration-200 shadow-sm hover:shadow-md">
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:gap-4 mb-3">
                                            <h3 class="text-lg md:text-xl font-bold text-zinc-800 dark:text-zinc-100">Prescription #{{ $prescription->appointment->apid }}</h3>
                                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full
                                                @if ($prescription->payment_status === 'pending') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                                                @elseif($prescription->payment_status === 'paid') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300
                                                @elseif($prescription->payment_status === 'failed') bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300
                                                @else bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300 @endif">
                                                {{ ucfirst($prescription->payment_status) }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                            <div>
                                                <p class="text-zinc-500 dark:text-zinc-400">Patient</p>
                                                <p class="text-zinc-700 dark:text-zinc-300 font-semibold">{{ $prescription->appointment->patient->name ?? 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-zinc-500 dark:text-zinc-400">Medication</p>
                                                <p class="text-zinc-700 dark:text-zinc-300 font-semibold">{{ $prescription->medication->name ?? 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-zinc-500 dark:text-zinc-400">Quantity</p>
                                                <p class="text-zinc-700 dark:text-zinc-300 font-semibold">{{ $prescription->quantity }}</p>
                                            </div>
                                            <div>
                                                <p class="text-zinc-500 dark:text-zinc-400">Prescribed</p>
                                                <p class="text-zinc-700 dark:text-zinc-300 font-semibold">{{ \Carbon\Carbon::parse($prescription->prescribed_date)->format('M d, Y') }}</p>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm mt-4">
                                            <div>
                                                <p class="text-zinc-500 dark:text-zinc-400">Amount</p>
                                                <p class="text-zinc-700 dark:text-zinc-300 font-semibold">₦{{ number_format($prescription->payment_amount ?: 0, 2) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-zinc-500 dark:text-zinc-400">Method</p>
                                                <p class="text-zinc-700 dark:text-zinc-300 font-semibold capitalize">{{ $prescription->payment_method ?: 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-zinc-500 dark:text-zinc-400">Status</p>
                                                <p class="text-zinc-700 dark:text-zinc-300 font-semibold capitalize">{{ $prescription->payment_status }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end lg:justify-start">
                                        <flux:button class="!bg-green-600 !text-white" variant="primary" wire:click="openBillingModal({{ $prescription->id }})">
                                            Process Billing
                                        </flux:button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-8">
                        {{ $prescriptions->links() }}
                    </div>
                @else
                    <div class="text-center py-12 md:py-16">
                        <svg class="mx-auto h-16 w-16 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-4 text-lg md:text-xl font-bold text-zinc-800 dark:text-zinc-100">No prescriptions found</h3>
                        <p class="mt-1 text-sm md:text-base text-zinc-600 dark:text-zinc-400">Try adjusting your search or filter criteria.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Billing Modal -->
    <flux:modal wire:model="showModal">
        <div class="p-4 md:p-6">
            <h2 class="text-xl md:text-2xl font-bold text-zinc-800 dark:text-zinc-100 mb-4 md:mb-6">Update Billing</h2>
            <div class="space-y-4 md:space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Payment Amount</label>
                    <flux:input type="number" step="0.01" wire:model="payment_amount" placeholder="0.00" />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Payment Method</label>
                    <flux:select wire:model="payment_method" placeholder="Select method">
                        <option value="">Select method</option>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="other">Other</option>
                    </flux:select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Payment Status</label>
                    <flux:select wire:model="payment_status" placeholder="Select status">
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                    </flux:select>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row justify-end gap-3 mt-6 md:mt-8">
                <flux:button variant="ghost" wire:click="closeModal">Cancel</flux:button>
                <flux:button variant="primary" wire:click="updateBilling">Save Changes</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
