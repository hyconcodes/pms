<?php

use Livewire\Volt\Component;
use App\Models\MedicalRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

new class extends Component {
    public $appointmentId;
    public $appointment;
    public $editMode = false;

    // Editable fields for doctor
    public $diagnosis;
    public $symptoms;
    public $notes;
    public $blood_pressure;
    public $lab_results;
    public $temperature;
    public $heart_rate;
    public $weight;
    public $height;
    public $allergies;
    // public $status;

    public function mount($appointment)
    {
        try {
            $this->appointmentId = $appointment instanceof MedicalRecord ? $appointment->id : $appointment;
            $this->loadAppointment();
        } catch (\Exception $e) {
            Log::error('Failed to load appointment', [
                'appointment_id' => $this->appointmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Failed to load appointment details.');
        }
    }

    public function loadAppointment()
    {
        try {
            $this->appointment = MedicalRecord::with(['doctor', 'patient'])
                ->where('id', $this->appointmentId)
                ->where('doctor_id', auth()->id())
                ->first();

            if ($this->appointment) {
                $this->diagnosis = $this->appointment->diagnosis;
                $this->symptoms = $this->appointment->symptoms;
                $this->notes = $this->appointment->notes;
                $this->blood_pressure = $this->appointment->blood_pressure;
                $this->lab_results = $this->appointment->lab_results;
                $this->temperature = $this->appointment->temperature;
                $this->heart_rate = $this->appointment->heart_rate;
                $this->weight = $this->appointment->weight;
                $this->height = $this->appointment->height;
                $this->allergies = $this->appointment->allergies;
                // $this->status = $this->appointment->status;
            }
        } catch (\Exception $e) {
            Log::error('Error loading appointment data', [
                'appointment_id' => $this->appointmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Unable to load appointment details.');
        }
    }

    public function toggleEdit()
    {
        $this->editMode = !$this->editMode;
        if (!$this->editMode) {
            $this->loadAppointment(); // Reset to original values
        }
    }

    public function saveAppointment()
    {
        try {
            $validatedData = $this->validate([
                'diagnosis' => 'nullable|string|max:1000',
                'symptoms' => 'nullable|string|max:1000',
                'notes' => 'nullable|string|max:2000',
                'blood_pressure' => 'nullable|string|max:50',
                'lab_results' => 'nullable|string|max:2000',
                'temperature' => 'nullable|numeric|between:30,45',
                'heart_rate' => 'nullable|integer|between:40,200',
                'weight' => 'nullable|numeric|between:1,300',
                'height' => 'nullable|numeric|between:50,250',
                'allergies' => 'nullable|string|max:1000',
                // 'status' => 'required|in:pending,completed,cancelled',
            ]);

            $this->appointment->update($validatedData);

            session()->flash('message', 'Appointment updated successfully!');
            session()->flash('alert-type', 'success');
            $this->editMode = false;
            $this->loadAppointment();
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error while updating appointment', [
                'appointment_id' => $this->appointmentId,
                'error' => $e->getMessage(),
                'validation_errors' => $e->errors(),
            ]);
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error while updating appointment', [
                'appointment_id' => $this->appointmentId,
                'error' => $e->getMessage(),
                'query' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);
            session()->flash('error', 'A database error occurred while updating the appointment.');
        } catch (\Exception $e) {
            Log::error('Failed to update appointment', [
                'appointment_id' => $this->appointmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'An unexpected error occurred while updating the appointment.');
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
                        <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">Update Appointment Medical Record</h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Reference: {{ $appointment->apid ?? 'N/A' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <flux:button variant="{{ $editMode ? 'outline' : 'primary' }}" wire:click="toggleEdit">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                            {{ $editMode ? 'Cancel' : 'Edit' }}
                        </flux:button>
                    </div>
                </div>

                <form wire:submit.prevent="saveAppointment" class="p-6 grid gap-6">
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

                    <!-- Reason & Specialty (read-only) -->
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Reason for visit</label>
                            <p class="text-zinc-800 dark:text-zinc-100 min-h-[2.5rem] bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3">{{ $appointment->reason_for_visit ?: 'No reason provided' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Specialty</label>
                            <p class="text-zinc-800 dark:text-zinc-100 min-h-[2.5rem] bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3">{{ $appointment->specialty ?: '—' }}</p>
                        </div>
                    </div>

                    <!-- Date & Time (read-only) -->
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Appointment Date</label>
                            <p class="text-zinc-800 dark:text-zinc-100 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3">{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('D, M d, Y') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Appointment Time</label>
                            <p class="text-zinc-800 dark:text-zinc-100 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3">{{ $appointment->appointment_time }}</p>
                        </div>
                    </div>

                    <!-- Status -->

                    <!-- Diagnosis -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Diagnosis</label>
                        @if ($editMode)
                            <flux:textarea wire:model="diagnosis" rows="3" placeholder="Enter diagnosis"/>
                            @error('diagnosis') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        @else
                            <p class="text-zinc-800 dark:text-zinc-100 min-h-[4.5rem] bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3">{{ $appointment->diagnosis ?: '—' }}</p>
                        @endif
                    </div>

                    <!-- Symptoms -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Symptoms</label>
                        @if ($editMode)
                            <flux:textarea wire:model="symptoms" rows="3" placeholder="List observed symptoms"/>
                            @error('symptoms') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        @else
                            <p class="text-zinc-800 dark:text-zinc-100 min-h-[4.5rem] bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3">{{ $appointment->symptoms ?: '—' }}</p>
                        @endif
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Notes</label>
                        @if ($editMode)
                            <flux:textarea wire:model="notes" rows="3" placeholder="Additional notes"/>
                            @error('notes') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        @else
                            <p class="text-zinc-800 dark:text-zinc-100 min-h-[4.5rem] bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3">{{ $appointment->notes ?: '—' }}</p>
                        @endif
                    </div>

                    <!-- Vitals -->
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Blood Pressure</label>
                            @if ($editMode)
                                <flux:input wire:model="blood_pressure" placeholder="e.g., 120/80"/>
                                @error('blood_pressure') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            @else
                                <p class="text-zinc-800 dark:text-zinc-100 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3">{{ $appointment->blood_pressure ?: '—' }}</p>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Temperature (°C)</label>
                            @if ($editMode)
                                <flux:input type="number" step="0.1" wire:model="temperature" placeholder="36.5"/>
                                @error('temperature') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            @else
                                <p class="text-zinc-800 dark:text-zinc-100 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3">{{ $appointment->temperature ?: '—' }}</p>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Heart Rate (bpm)</label>
                            @if ($editMode)
                                <flux:input type="number" wire:model="heart_rate" placeholder="72"/>
                                @error('heart_rate') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            @else
                                <p class="text-zinc-800 dark:text-zinc-100 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3">{{ $appointment->heart_rate ?: '—' }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Weight (kg)</label>
                            @if ($editMode)
                                <flux:input type="number" step="0.1" wire:model="weight" placeholder="70.0"/>
                                @error('weight') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            @else
                                <p class="text-zinc-800 dark:text-zinc-100 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3">{{ $appointment->weight ?: '—' }}</p>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Height (cm)</label>
                            @if ($editMode)
                                <flux:input type="number" wire:model="height" placeholder="175"/>
                                @error('height') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            @else
                                <p class="text-zinc-800 dark:text-zinc-100 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3">{{ $appointment->height ?: '—' }}</p>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Allergies</label>
                            @if ($editMode)
                                <flux:input wire:model="allergies" placeholder="e.g., Penicillin"/>
                                @error('allergies') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            @else
                                <p class="text-zinc-800 dark:text-zinc-100 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3">{{ $appointment->allergies ?: '—' }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Lab Results -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Lab Results</label>
                        @if ($editMode)
                            <flux:textarea wire:model="lab_results" rows="3" placeholder="Enter lab results"/>
                            @error('lab_results') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        @else
                            <p class="text-zinc-800 dark:text-zinc-100 min-h-[4.5rem] bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3">{{ $appointment->lab_results ?: '—' }}</p>
                        @endif
                    </div>

                    <!-- Save bar -->
                    @if ($editMode)
                        <div class="flex items-center justify-end pt-4 border-t border-zinc-200/60 dark:border-zinc-700/60">
                            <flux:button type="submit" variant="primary">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v2h2a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8c0-1.1.9-2 2-2h2V4zm4 2H8v2h1V6zm3 0h-2v2h2V6zM5 8v10h10V8H5z"/></svg>
                                Save Changes
                            </flux:button>
                        </div>
                    @endif
                </form>
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
