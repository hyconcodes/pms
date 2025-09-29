<?php

use Livewire\Volt\Component;
use App\Models\MedicalRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

new class extends Component {
    public $appointmentId;
    public $appointment;
    public $editMode = false;

    // Editable fields
    public $reason_for_visit;
    public $appointment_date;
    public $appointment_time;

    public function mount($appointment)
    {
        try {
            // Volt automatically resolves route-model binding when parameter name matches
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
                ->where('patient_id', auth()->id())
                ->first();

            if ($this->appointment) {
                $this->reason_for_visit = $this->appointment->reason_for_visit;
                $this->appointment_date = $this->appointment->appointment_date;
                $this->appointment_time = $this->appointment->appointment_time;
                
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
                'reason_for_visit' => 'nullable|string|max:1000',
                'appointment_date' => 'required|date|after_or_equal:today',
                'appointment_time' => 'required|in:morning,afternoon',
                
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

    public function deleteAppointment()
    {
        try {
            if ($this->appointment && $this->appointment->status === 'pending') {
                $this->appointment->delete();
                session()->flash('message', 'Appointment deleted successfully!');
                return redirect()->route('patient.dashboard');
            } else {
                session()->flash('error', 'Only pending appointments can be deleted.');
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete appointment', [
                'appointment_id' => $this->appointmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'An error occurred while deleting the appointment.');
        }
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800">
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
            <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-2xl shadow-xl border border-slate-200/50 dark:border-slate-700/50 overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-5 border-b border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Appointment</h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Reference #{{ $appointment->id }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if ($appointment->status === 'pending')
                            <flux:button variant="danger" size="sm" x-on:click.prevent="if (confirm('Delete this appointment?')) $wire.deleteAppointment()">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                Delete
                            </flux:button>
                        @endif
                        <flux:button variant="{{ $editMode ? 'outline' : 'primary' }}" size="sm" wire:click="toggleEdit">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                            {{ $editMode ? 'Cancel' : 'Edit' }}
                        </flux:button>
                    </div>
                </div>

                <form wire:submit.prevent="saveAppointment" class="p-6 grid gap-6">
                    <!-- Doctor & Patient -->
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Doctor</p>
                            <p class="mt-1 text-slate-800 dark:text-slate-100 font-semibold">Dr. {{ $appointment->doctor->name }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Patient</p>
                            <p class="mt-1 text-slate-800 dark:text-slate-100 font-semibold">{{ $appointment->patient->name }}</p>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Reason for visit</label>
                        @if ($editMode)
                            <flux:textarea wire:model="reason_for_visit" rows="3" placeholder="Describe the reason for this appointment"/>
                            @error('reason_for_visit') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        @else
                            <p class="text-slate-800 dark:text-slate-100 min-h-[4.5rem] bg-slate-50 dark:bg-slate-800/50 rounded-lg p-3">{{ $appointment->reason_for_visit ?: 'No reason provided' }}</p>
                        @endif
                    </div>

                    <!-- Date & Time -->
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date</label>
                            @if ($editMode)
                                <flux:input type="date" wire:model="appointment_date"/>
                                @error('appointment_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            @else
                                <p class="text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-800/50 rounded-lg p-3">{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('D, M d, Y') }}</p>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Time slot</label>
                            @if ($editMode)
                                <flux:select wire:model="appointment_time" placeholder="Select slot">
                                    <option value="morning">Morning</option>
                                    <option value="afternoon">Afternoon</option>
                                </flux:select>
                                @error('appointment_time') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            @else
                                <p class="text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-800/50 rounded-lg p-3 capitalize">{{ $appointment->appointment_time }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Read-only medical info -->
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Symptoms</p>
                            <p class="mt-1 text-slate-800 dark:text-slate-100">{{ $appointment->symptoms ?: '—' }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Diagnosis</p>
                            <p class="mt-1 text-slate-800 dark:text-slate-100">{{ $appointment->diagnosis ?: '—' }}</p>
                        </div>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Notes</p>
                        <p class="mt-1 text-slate-800 dark:text-slate-100">{{ $appointment->notes ?: '—' }}</p>
                    </div>

                    <!-- Vitals -->
                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Blood Pressure</p>
                            <p class="mt-1 text-slate-800 dark:text-slate-100">{{ $appointment->blood_pressure ?: '—' }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Temperature (°C)</p>
                            <p class="mt-1 text-slate-800 dark:text-slate-100">{{ $appointment->temperature ?: '—' }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Heart Rate (bpm)</p>
                            <p class="mt-1 text-slate-800 dark:text-slate-100">{{ $appointment->heart_rate ?: '—' }}</p>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Weight (kg)</p>
                            <p class="mt-1 text-slate-800 dark:text-slate-100">{{ $appointment->weight ?: '—' }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Height (cm)</p>
                            <p class="mt-1 text-slate-800 dark:text-slate-100">{{ $appointment->height ?: '—' }}</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Allergies</p>
                            <p class="mt-1 text-slate-800 dark:text-slate-100">{{ $appointment->allergies ?: '—' }}</p>
                        </div>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Lab Results</p>
                        <p class="mt-1 text-slate-800 dark:text-slate-100">{{ $appointment->lab_results ?: '—' }}</p>
                    </div>

                    <!-- Save bar -->
                    @if ($editMode)
                        <div class="flex items-center justify-end pt-4 border-t border-slate-200/60 dark:border-slate-700/60">
                            <flux:button type="submit" variant="primary">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v2h2a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8c0-1.1.9-2 2-2h2V4zm4 2H8v2h1V6zm3 0h-2v2h2V6zM5 8v10h10V8H5z"/></svg>
                                Save Changes
                            </flux:button>
                        </div>
                    @endif
                </form>
            </div>
        @else
            <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-2xl shadow-xl border border-slate-200/50 dark:border-slate-700/50 p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h3 class="mt-4 text-lg font-semibold text-slate-800 dark:text-slate-100">Appointment not found</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">It may have been removed or you don’t have permission to view it.</p>
                <div class="mt-6">
                    <flux:button variant="primary" href="{{ route('patient.dashboard') }}">Back to Dashboard</flux:button>
                </div>
            </div>
        @endif
    </div>
</div>
