<?php

use Livewire\Volt\Component;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\Medication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $perPage = 10;

    // Modal state
    public $showPrescriptionModal = false;
    public $modalAppointment;
    public $modalPrescriptions = [];

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
        return redirect()->route('patient.appointment.details', $appointmentId);
    }

    public function openPrescriptionModal($appointmentId)
    {
        try {
            $this->modalAppointment = MedicalRecord::with(['doctor', 'patient'])
                ->where('id', $appointmentId)
                ->where('patient_id', Auth::id())
                ->first();

            if ($this->modalAppointment) {
                $this->modalPrescriptions = Prescription::with('medication')
                    ->where('appointment_id', $appointmentId)
                    ->get();
                $this->showPrescriptionModal = true;
            }
        } catch (\Exception $e) {
            Log::error('Failed to load prescription modal', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to load prescription details.');
        }
    }

    public function closePrescriptionModal()
    {
        $this->showPrescriptionModal = false;
        $this->reset(['modalAppointment', 'modalPrescriptions']);
    }

    public function with(): array
    {
        try {
            $query = MedicalRecord::with(['doctor'])
                ->where('patient_id', Auth::id())
                ->latest();

            if ($this->search) {
                $query->whereHas('doctor', function ($q) {
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
            Log::error('Failed to load patient appointments', [
                'patient_id' => Auth::id(),
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

<div class="min-h-screen">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div
            class="bg-white/80 dark:bg-zinc-800/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-green-100 dark:border-zinc-700 overflow-hidden">
            <!-- Header -->
            <div class="px-8 py-6 border-b border-green-100 dark:border-zinc-700">
                <h1 class="text-3xl font-extrabold text-green-900 dark:text-white">My Appointments</h1>
                <p class="text-sm text-green-600 dark:text-zinc-300 mt-2">Track and review your upcoming and past
                    appointments</p>
            </div>

            <!-- Filters -->
            <div class="p-8 border-b border-green-100 dark:border-zinc-700">
                <div class="grid md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-green-800 dark:text-zinc-200 mb-2">Search
                            Doctor</label>
                        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by doctor name..." />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-green-800 dark:text-zinc-200 mb-2">Status</label>
                        <flux:select wire:model.live="statusFilter" placeholder="All statuses">
                            <option value="">All</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </flux:select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-green-800 dark:text-zinc-200 mb-2">Per
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
            <div class="p-8">
                @if ($appointments->count() > 0)
                    <div class="space-y-6">
                        @foreach ($appointments as $appointment)
                            <div
                                class="bg-green-50 dark:bg-zinc-700/50 rounded-2xl p-6 hover:shadow-lg transition-shadow">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-4 mb-3">
                                            <h3 class="text-xl font-bold text-green-900 dark:text-white">
                                                Dr. {{ $appointment->doctor->name }}
                                            </h3>
                                            <span
                                                class="px-3 py-1 text-xs font-bold rounded-full
                                                @if ($appointment->status === 'pending') bg-amber-200 text-amber-900 dark:bg-amber-900/30 dark:text-amber-200
                                                @elseif($appointment->status === 'completed') bg-emerald-200 text-emerald-900 dark:bg-emerald-900/30 dark:text-emerald-200
                                                @else bg-rose-200 text-rose-900 dark:bg-rose-900/30 dark:text-rose-200 @endif">
                                                {{ ucfirst($appointment->status) }}
                                            </span>
                                        </div>
                                        <div class="grid md:grid-cols-3 gap-4 text-sm">
                                            <div>
                                                <p class="text-green-600 dark:text-zinc-400">Date & Time</p>
                                                <p class="text-green-800 dark:text-zinc-100 font-semibold">
                                                    {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('D, M d, Y') }}
                                                    at {{ $appointment->appointment_time }}
                                                </p>
                                            </div>

                                            <div>
                                                <p class="text-green-600 dark:text-zinc-400">Reference</p>
                                                <p class="text-green-800 dark:text-zinc-100 font-semibold font-mono">
                                                    {{ $appointment->apid }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ml-6 flex items-center gap-2">
                                        <flux:button variant="primary" size="sm"
                                            wire:click="viewAppointment({{ $appointment->id }})">
                                            View Record
                                        </flux:button>
                                        @if ($appointment->status === 'completed')
                                            <flux:button variant="primary" class="!bg-blue-700 !text-white" size="sm" outline
                                                wire:click="openPrescriptionModal({{ $appointment->id }})">
                                                View Prescription
                                            </flux:button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-8">
                        {{ $appointments->links() }}
                    </div>
                @else
                    <div class="text-center py-16">
                        <svg class="mx-auto h-16 w-16 text-green-300 dark:text-zinc-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-6 text-xl font-bold text-green-900 dark:text-white">No appointments found</h3>
                        <p class="mt-2 text-sm text-green-600 dark:text-zinc-400">Try adjusting your search or filter
                            criteria.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Prescription Modal -->
    @if($showPrescriptionModal)
        <div
            x-data="{ open: @entangle('showPrescriptionModal') }"
            x-show="open"
            x-cloak
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur"
            @keydown.escape.window="open = false; $wire.closePrescriptionModal()"
        >
            <div
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative w-full max-w-3xl max-h-[90vh] overflow-y-auto bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl border border-zinc-200 dark:border-zinc-700"
            >
                <!-- Modal Header -->
                <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 rounded-t-2xl">
                    <div>
                        <h2 class="text-xl font-bold text-zinc-800 dark:text-zinc-100">Prescription Details</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Appointment: {{ $modalAppointment->apid ?? 'N/A' }}</p>
                    </div>
                    <button
                        type="button"
                        @click="open = false; $wire.closePrescriptionModal()"
                        class="p-2 rounded-full text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="px-6 py-6 space-y-6">
                    @if($modalAppointment)
                        <!-- Doctor & Patient -->
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-xl p-4">
                                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Doctor</p>
                                <p class="mt-1 text-zinc-800 dark:text-zinc-100 font-semibold">Dr. {{ $modalAppointment->doctor->name }}</p>
                            </div>
                            <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-xl p-4">
                                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Patient</p>
                                <p class="mt-1 text-zinc-800 dark:text-zinc-100 font-semibold">{{ $modalAppointment->patient->name }}</p>
                            </div>
                        </div>

                        <!-- Prescriptions List -->
                        @if($modalPrescriptions->count())
                            <div>
                                <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Prescribed Medications</h3>
                                <div class="space-y-4">
                                    @foreach($modalPrescriptions as $prescription)
                                        <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-xl p-4">
                                            <div class="flex items-start justify-between">
                                                <div>
                                                    <p class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $prescription->medication->name }} ({{ $prescription->medication->dosage }})</p>
                                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Quantity: {{ $prescription->quantity }}</p>
                                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Instructions: {{ $prescription->instructions }}</p>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-2">Prescribed: {{ \Carbon\Carbon::parse($prescription->prescribed_date)->format('M d, Y') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <h3 class="mt-4 text-lg font-semibold text-zinc-800 dark:text-zinc-100">No prescriptions found</h3>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">There are no prescriptions for this appointment.</p>
                            </div>
                        @endif
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="sticky bottom-0 z-10 flex items-center justify-end px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 rounded-b-2xl">
                    <flux:button variant="outline" @click="open = false; $wire.closePrescriptionModal()">
                        Close
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
