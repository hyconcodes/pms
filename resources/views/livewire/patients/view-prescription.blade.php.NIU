<?php

use Livewire\Volt\Component;
use App\Models\MedicalRecord;
use App\Models\Medication;
use App\Models\Prescription;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

new class extends Component {
    public $appointmentId;
    public $appointment;
    public $medications = [];
    public $prescriptions = [];

    // Prescription fields
    public $medication_id;
    public $quantity;
    public $instructions;
    public $prescribed_date;

    public function mount($appointment)
    {
        try {
            $this->appointmentId = $appointment instanceof MedicalRecord ? $appointment->id : $appointment;
            $this->loadAppointment();
            $this->loadMedications();
            $this->loadPrescriptions();
            $this->prescribed_date = now()->toDateString();
        } catch (\Exception $e) {
            Log::error('Failed to load prescription page', [
                'appointment_id' => $this->appointmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Failed to load prescription details.');
        }
    }

    public function loadAppointment()
    {
        $this->appointment = MedicalRecord::with(['doctor', 'patient'])
            ->where('id', $this->appointmentId)
            ->where('doctor_id', auth()->id())
            ->first();
    }

    public function loadMedications()
    {
        $this->medications = Medication::all();
    }

    public function loadPrescriptions()
    {
        $this->prescriptions = Prescription::with('medication')
            ->where('appointment_id', $this->appointmentId)
            ->get();
    }

    public function addPrescription()
    {
        $this->validate([
            'medication_id' => 'required|exists:medications,id',
            'quantity' => 'required|string|max:255',
            'instructions' => 'required|string|max:2000',
            'prescribed_date' => 'required|date',
        ]);

        try {
            Prescription::create([
                'appointment_id' => $this->appointmentId,
                'medication_id' => $this->medication_id,
                'quantity' => $this->quantity,
                'instructions' => $this->instructions,
                'prescribed_date' => $this->prescribed_date,
            ]);

            session()->flash('message', 'Prescription added successfully!');
            session()->flash('alert-type', 'success');

            $this->reset(['medication_id', 'quantity', 'instructions', 'prescribed_date']);
            $this->prescribed_date = now()->toDateString();
            $this->loadPrescriptions();
        } catch (QueryException $e) {
            Log::error('Database error while adding prescription', [
                'appointment_id' => $this->appointmentId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'A database error occurred while adding the prescription.');
        } catch (\Exception $e) {
            Log::error('Failed to add prescription', [
                'appointment_id' => $this->appointmentId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'An unexpected error occurred while adding the prescription.');
        }
    }

    public function deletePrescription($id)
    {
        try {
            Prescription::where('id', $id)->where('appointment_id', $this->appointmentId)->delete();
            session()->flash('message', 'Prescription removed successfully!');
            session()->flash('alert-type', 'success');
            $this->loadPrescriptions();
        } catch (\Exception $e) {
            Log::error('Failed to delete prescription', [
                'prescription_id' => $id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to remove prescription.');
        }
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-900 dark:to-zinc-800">
    <!-- Floating toast -->
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" x-transition
            class="fixed top-6 right-6 z-50 flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg
            {{ session('alert-type') === 'error' ? 'bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-300' : 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-300' }}">
            @if (session('alert-type') === 'error')
                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            @else
                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            @endif
            <span class="text-sm font-medium">{{ session('message') }}</span>
            <button @click="show = false" class="ml-auto text-current/60 hover:text-current">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
        </div>
    @endif

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if ($appointment)
            <div class="bg-white/70 dark:bg-zinc-800/70 backdrop-blur-xl rounded-2xl shadow-xl border border-zinc-200/50 dark:border-zinc-700/50 overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-5 border-b border-zinc-200/60 dark:border-zinc-700/60 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">Add Prescription</h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Appointment: {{ $appointment->apid ?? 'N/A' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <flux:button variant="outline" href="{{ route('doctor.view.all.appointment') }}">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Back to Record
                        </flux:button>
                    </div>
                </div>

                <form wire:submit.prevent="addPrescription" class="p-6 grid gap-6">
                    <!-- Doctor & Patient -->
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-4">
                            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Doctor</p>
                            <p class="mt-1 text-zinc-800 dark:text-zinc-100 font-semibold">Dr. {{ $appointment->doctor->name }}</p>
                        </div>
                        <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-4">
                            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Patient</p>
                            <p class="mt-1 text-zinc-800 dark:text-zinc-100 font-semibold">{{ $appointment->patient->name }}</p>
                        </div>
                    </div>

                    <!-- Medication -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Medication</label>
                        <flux:select wire:model="medication_id" placeholder="Select medication">
                            @foreach($medications as $med)
                                <option value="{{ $med->id }}">{{ $med->name }} ({{ $med->dosage }})</option>
                            @endforeach
                        </flux:select>
                        @error('medication_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- Quantity -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Quantity</label>
                        <flux:input wire:model="quantity" placeholder="e.g., 30 tablets"/>
                        @error('quantity') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- Instructions -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Instructions</label>
                        <flux:textarea wire:model="instructions" rows="3" placeholder="e.g., Take once daily with food"/>
                        @error('instructions') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- Prescribed Date -->
                    <div class='hidden'>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Prescribed Date</label>
                        <flux:input type="date" value="{{ now()->toDateString() }}" disabled />
                        <input type="hidden" wire:model="prescribed_date" value="{{ now()->toDateString() }}" />
                        @error('prescribed_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- Save -->
                    <div class="flex items-center justify-end pt-4 border-t border-zinc-200/60 dark:border-zinc-700/60">
                        <flux:button type="submit" variant="primary">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v2h2a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8c0-1.1.9-2 2-2h2V4zm4 2H8v2h1V6zm3 0h-2v2h2V6zM5 8v10h10V8H5z"/></svg>
                            Add Prescription
                        </flux:button>
                    </div>
                </form>

                <!-- Existing Prescriptions -->
                @if($prescriptions->count())
                    <div class="px-6 pb-6">
                        <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Current Prescriptions</h2>
                        <div class="space-y-3">
                            @foreach($prescriptions as $prescription)
                                <div class="flex items-center justify-between bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                    <div>
                                        <p class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $prescription->medication->name }} ({{ $prescription->medication->dosage }})</p>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Quantity: {{ $prescription->quantity }}</p>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Instructions: {{ $prescription->instructions }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1">Prescribed: {{ \Carbon\Carbon::parse($prescription->prescribed_date)->format('M d, Y') }}</p>
                                    </div>
                                    <flux:button variant="outline" size="sm" wire:click="deletePrescription({{ $prescription->id }})" onclick="confirm('Are you sure?') || event.stopImmediatePropagation()">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4 0a1 1 0 012 0v6a1 1 0 11-2 0V8z" clip-rule="evenodd"/></svg>
                                    </flux:button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-white/70 dark:bg-zinc-800/70 backdrop-blur-xl rounded-2xl shadow-xl border border-zinc-200/50 dark:border-zinc-700/50 p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h3 class="mt-4 text-lg font-semibold text-zinc-800 dark:text-zinc-100">Appointment not found</h3>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">It may have been removed or you don’t have permission to view it.</p>
                <div class="mt-6">
                    <flux:button variant="primary" href="{{ route('doctor.dashboard') }}">Back to Dashboard</flux:button>
                </div>
            </div>
        @endif
    </div>
</div>
