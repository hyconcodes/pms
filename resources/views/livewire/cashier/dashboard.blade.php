<?php

use Livewire\Volt\Component;
use App\Models\MedicalRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

new class extends Component {
    public $lastAppointment;
    public $appointments;
    public $showAll = false;

    // Stats
    public $processedToday;
    public $revenueThisMonth;

    // Modal state
    public $showModal = false;
    public $selectedAppointment;
    public $payment_method;
    public $payment_amount;

    public function mount()
    {
        try {
            $this->loadLastAppointment();
            $this->loadAppointments();
            $this->loadStats();
        } catch (\Exception $e) {
            Log::error('Failed to initialize cashier dashboard', [
                'cashier_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Failed to load dashboard data. Please try again later.');
        }
    }

    public function loadLastAppointment()
    {
        try {
            $this->lastAppointment = MedicalRecord::with('patient')
                ->where('status', 'pending')
                ->whereNull('payment_method')
                ->orderBy('appointment_date', 'desc')
                ->first();
        } catch (QueryException $e) {
            Log::error('Database error while loading last appointment', [
                'cashier_id' => auth()->id(),
                'error' => $e->getMessage(),
                'query' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);
            session()->flash('error', 'Unable to load appointment data. Our team has been notified.');
            $this->lastAppointment = null;
        } catch (\Exception $e) {
            Log::error('Unexpected error while loading last appointment', [
                'cashier_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'An unexpected error occurred. Please try again later.');
            $this->lastAppointment = null;
        }
    }

    public function loadAppointments()
    {
        try {
            $this->appointments = MedicalRecord::with('patient')
                ->where('status', 'pending')
                ->whereNull('payment_method')
                ->orderBy('appointment_date', 'desc')
                ->take(3)
                ->get();
        } catch (QueryException $e) {
            Log::error('Database error while loading appointments', [
                'cashier_id' => auth()->id(),
                'error' => $e->getMessage(),
                'query' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);
            session()->flash('error', 'Unable to load appointments. Our team has been notified.');
            $this->appointments = collect();
        } catch (\Exception $e) {
            Log::error('Unexpected error while loading appointments', [
                'cashier_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'An unexpected error occurred. Please try again later.');
            $this->appointments = collect();
        }
    }

    public function loadStats()
    {
        try {
            // Processed Today
            $this->processedToday = MedicalRecord::whereDate('updated_at', today())
                ->whereNotNull('payment_amount')
                ->sum('payment_amount') ?? 0;

            // Revenue This Month
            $this->revenueThisMonth = MedicalRecord::whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->whereNotNull('payment_amount')
                ->sum('payment_amount') ?? 0;
        } catch (\Exception $e) {
            Log::error('Failed to load cashier stats', [
                'cashier_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            $this->processedToday = 0;
            $this->revenueThisMonth = 0;
        }
    }

    public function openProcessModal($appointmentId)
    {
        try {
            $this->selectedAppointment = MedicalRecord::with('patient')->findOrFail($appointmentId);
            $this->payment_method = $this->selectedAppointment->payment_method;
            $this->payment_amount = $this->selectedAppointment->payment_amount;
            $this->showModal = true;
        } catch (\Exception $e) {
            Log::error('Failed to open process modal', [
                'cashier_id' => auth()->id(),
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Unable to open appointment details. Please try again.');
        }
    }

    public function closeModal()
    {
        $this->reset(['showModal', 'selectedAppointment', 'payment_method', 'payment_amount']);
    }

    public function savePayment()
    {
        $this->validate([
            'payment_method' => 'required|in:cash,card,bank_transfer,other',
            'payment_amount' => 'required|numeric|min:0',
        ]);

        try {
            $this->selectedAppointment->update([
                'payment_method' => $this->payment_method,
                'payment_amount' => $this->payment_amount,
                'status' => 'completed',
            ]);

            session()->flash('message', 'Payment processed successfully and completed.');
            session()->flash('alert-type', 'success');

            $this->closeModal();
            $this->loadLastAppointment();
            $this->loadAppointments();
            $this->loadStats();
        } catch (\Exception $e) {
            Log::error('Failed to save payment', [
                'cashier_id' => auth()->id(),
                'appointment_id' => $this->selectedAppointment->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to process payment. Please try again.');
        }
    }
}; ?>

<div class="min-h-screen p-4 sm:p-6 lg:p-4">
    @if (session()->has('message'))
        <div class="fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg {{ session('alert-type') === 'error' ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700' }} border">
            <div class="flex items-center">
                @if (session('alert-type') === 'error')
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                @else
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                @endif
                <span class="font-medium">{{ session('message') }}</span>
                <button type="button" class="ml-4 text-gray-500 hover:text-gray-700" @click="$el.parentElement.parentElement.remove()">
                    <span class="sr-only">Close</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            @if (session('details'))
                <div class="mt-2 text-sm">
                    {{ session('details') }}
                </div>
            @endif
        </div>
    @endif

    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-zinc-900 dark:text-white">Cashier Dashboard</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Quick Stats -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold mb-4 text-zinc-800 dark:text-white">Quick Stats</h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-600 dark:text-zinc-300">Latest Appointment</span>
                        <span class="text-lg font-bold text-green-600">
                            {{ $lastAppointment ? \Carbon\Carbon::parse($lastAppointment->appointment_date)->format('M d') : 'None' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-600 dark:text-zinc-300">View All</span>
                        <flux:button href="{{ route('cashier.view.all.billing') }}" size="sm" class="px-2 py-1 text-xs font-medium !text-green-600 border !border-green-600 rounded hover:!bg-green-600 hover:!text-white transition">
                            Patients Invoices
                        </flux:button>
                    </div>
                </div>
            </div>

            <!-- Processed Today & Revenue This Month -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6 col-span-1 md:col-span-2">
                <h2 class="text-xl font-semibold mb-4 text-zinc-800 dark:text-white">Consultation Performance</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Processed Today -->
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-zinc-600 dark:text-zinc-300">Processed Today</p>
                            <p class="text-2xl font-bold text-zinc-900 dark:text-white">₦{{ number_format($processedToday, 2) }}</p>
                        </div>
                    </div>

                    <!-- Revenue This Month -->
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v.01M12 8v.01M12 8v-1m0 8v1m0-8c-1.11 0-2.08.402-2.599 1M12 8c-1.11 0-2.08.402-2.599 1M7 12h10m-7 4h4"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-zinc-600 dark:text-zinc-300">Revenue This Month</p>
                            <p class="text-2xl font-bold text-zinc-900 dark:text-white">₦{{ number_format($revenueThisMonth, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Appointments Horizontal List -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold mb-4 text-zinc-800 dark:text-white">Recent Appointments</h2>
            <div class="space-y-3">
                @forelse ($appointments as $appointment)
                    <div class="border dark:border-zinc-700 rounded-lg p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div>
                                    <h3 class="font-medium text-zinc-900 dark:text-white">{{ $appointment->apid ?? 'N/A' }}</h3>
                                    <h3 class="font-medium text-zinc-900 dark:text-white">{{ $appointment->patient->name }}</h3>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $appointment->specialty }} · {{ $appointment->reason_for_visit }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y') }}
                                    </p>
                                    <p class="text-sm font-medium {{ $appointment->payment_amount ? 'text-green-600' : 'text-red-600' }} dark:text-white">
                                        {{ $appointment->payment_amount ? '₦' . number_format($appointment->payment_amount, 2) : 'Not yet paid' }}
                                    </p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ ucfirst($appointment->appointment_time) }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                </div>
                                <flux:button wire:click="openProcessModal({{ $appointment->id }})" size="sm" class="px-3 py-1 text-xs font-medium !text-blue-600 border !border-blue-600 rounded hover:!bg-blue-600 hover:!text-white transition">
                                    Process
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <p class="text-zinc-600 dark:text-zinc-300">No appointments found</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Process Payment Modal -->
    @if($showModal && $selectedAppointment)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-lg w-full max-w-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Process Payment</h3>
                    <button wire:click="closeModal" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4 mb-6">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Patient</p>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $selectedAppointment->patient->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Specialty</p>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $selectedAppointment->specialty }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Appointment Date</p>
                        <p class="font-medium text-zinc-900 dark:text-white">
                            {{ \Carbon\Carbon::parse($selectedAppointment->appointment_date)->format('M d, Y') }}
                            at {{ ucfirst($selectedAppointment->appointment_time) }}
                        </p>
                    </div>
                </div>

                <form wire:submit.prevent="savePayment" class="space-y-4">
                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Payment Method</label>
                        <flux:select id="payment_method" wire:model="payment_method" class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Select method</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="other">Other</option>
                        </flux:select>
                        @error('payment_method')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="payment_amount" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Payment Amount (₦)</label>
                        <flux:input id="payment_amount" type="number" step="0.01" wire:model="payment_amount" class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="0.00"/>
                        @error('payment_amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-3 pt-4">
                        <flux:button type="button" wire:click="closeModal" variant="outline" class="w-full">
                            Cancel
                        </flux:button>
                        <flux:button type="submit" variant="primary" class="w-full">
                            Save Payment
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
