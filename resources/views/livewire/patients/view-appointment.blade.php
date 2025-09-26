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
    public $diagnosis;
    public $symptoms;
    public $notes;
    public $blood_pressure;
    public $lab_results;
    public $appointment_date;
    public $appointment_time;
    public $temperature;
    public $heart_rate;
    public $weight;
    public $height;
    public $allergies;
    public $status;
    public $visit_type;

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
                $this->diagnosis = $this->appointment->diagnosis;
                $this->symptoms = $this->appointment->symptoms;
                $this->notes = $this->appointment->notes;
                $this->blood_pressure = $this->appointment->blood_pressure;
                $this->lab_results = $this->appointment->lab_results;
                $this->appointment_date = $this->appointment->appointment_date;
                $this->appointment_time = $this->appointment->appointment_time;
                $this->temperature = $this->appointment->temperature;
                $this->heart_rate = $this->appointment->heart_rate;
                $this->weight = $this->appointment->weight;
                $this->height = $this->appointment->height;
                $this->allergies = $this->appointment->allergies;
                $this->status = $this->appointment->status;
                $this->visit_type = $this->appointment->visit_type;
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
                'appointment_date' => 'required|date',
                'appointment_time' => 'required|in:morning,afternoon',
                'temperature' => 'nullable|numeric|min:30|max:45',
                'heart_rate' => 'nullable|integer|min:40|max:200',
                'weight' => 'nullable|numeric|min:0|max:500',
                'height' => 'nullable|numeric|min:0|max:300',
                'allergies' => 'nullable|string|max:1000',
                'status' => 'required|in:pending,confirmed,completed,cancelled',
                'visit_type' => 'required|in:in-person,virtual',
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

<div>
    <div class="min-h-screen bg-zinc-50 dark:bg-zinc-900 p-4 sm:p-6 lg:p-8">
        @if (session()->has('message'))
            <div
                class="fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg {{ session('alert-type') === 'error' ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700' }} border">
                <div class="flex items-center">
                    @if (session('alert-type') === 'error')
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    @else
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                    @endif
                    <span class="font-medium">{{ session('message') }}</span>
                    <button type="button" class="ml-4 text-gray-500 hover:text-gray-700"
                        @click="$el.parentElement.parentElement.remove()">
                        <span class="sr-only">Close</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <div class="max-w-4xl mx-auto">
            @if ($appointment)
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Appointment Details</h1>
                        <div class="flex space-x-3">
                            @if ($appointment->status === 'pending')
                                <flux:button wire:click="deleteAppointment" 
                                    class="px-4 py-2 !bg-red-600 !text-white rounded-lg hover:!bg-red-700 transition"
                                    onclick="return confirm('Are you sure you want to delete this appointment?')">
                                    Delete
                                </flux:button>
                            @endif
                            <flux:button wire:click="toggleEdit" 
                                class="px-4 py-2 {{ $editMode ? '!bg-gray-600' : '!bg-green-600' }} !text-white rounded-lg hover:opacity-90 transition">
                                {{ $editMode ? 'Cancel Edit' : 'Edit Appointment' }}
                            </flux:button>
                        </div>
                    </div>

                    <form wire:submit.prevent="saveAppointment" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Doctor</label>
                                <p class="mt-1 text-zinc-900 dark:text-white">Dr. {{ $appointment->doctor->name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Patient</label>
                                <p class="mt-1 text-zinc-900 dark:text-white">{{ $appointment->patient->name }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Appointment Date</label>
                                @if ($editMode)
                                    <flux:input type="date" wire:model="appointment_date" 
                                        class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm" />
                                    @error('appointment_date') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                @else
                                    <p class="mt-1 text-zinc-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($appointment_date)->format('M d, Y') }}
                                    </p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Appointment Time</label>
                                @if ($editMode)
                                    <flux:select wire:model="appointment_time" class="mt-1 block w-full">
                                        <option value="morning">Morning</option>
                                        <option value="afternoon">Afternoon</option>
                                    </flux:select>
                                    @error('appointment_time') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                @else
                                    <p class="mt-1 text-zinc-900 dark:text-white capitalize">{{ $appointment_time }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Visit Type</label>
                                @if ($editMode)
                                    <flux:select wire:model="visit_type" class="mt-1 block w-full">
                                        <option value="in-person">In-Person</option>
                                        <option value="virtual">Virtual</option>
                                    </flux:select>
                                    @error('visit_type') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                @else
                                    <p class="mt-1 text-zinc-900 dark:text-white capitalize">{{ $visit_type }}</p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Status</label>
                                @if ($editMode)
                                    <flux:select wire:model="status" class="mt-1 block w-full">
                                        <option value="pending">Pending</option>
                                        <option value="confirmed">Confirmed</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </flux:select>
                                    @error('status') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                @else
                                    <p class="mt-1 text-zinc-900 dark:text-white capitalize">{{ $status }}</p>
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Symptoms</label>
                            @if ($editMode)
                                <flux:textarea wire:model="symptoms" rows="3" 
                                    class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm" />
                                @error('symptoms') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            @else
                                <p class="mt-1 text-zinc-900 dark:text-white">{{ $symptoms ?: 'No symptoms recorded' }}</p>
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Diagnosis</label>
                            @if ($editMode)
                                <flux:textarea wire:model="diagnosis" rows="3" 
                                    class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm" />
                                @error('diagnosis') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            @else
                                <p class="mt-1 text-zinc-900 dark:text-white">{{ $diagnosis ?: 'No diagnosis recorded' }}</p>
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Notes</label>
                            @if ($editMode)
                                <flux:textarea wire:model="notes" rows="3" 
                                    class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm" />
                                @error('notes') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            @else
                                <p class="mt-1 text-zinc-900 dark:text-white">{{ $notes ?: 'No notes available' }}</p>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Blood Pressure</label>
                                @if ($editMode)
                                    <flux:input wire:model="blood_pressure" 
                                        class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm" 
                                        placeholder="e.g., 120/80" />
                                    @error('blood_pressure') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                @else
                                    <p class="mt-1 text-zinc-900 dark:text-white">{{ $blood_pressure ?: 'Not recorded' }}</p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Temperature (°C)</label>
                                @if ($editMode)
                                    <flux:input type="number" step="0.1" wire:model="temperature" 
                                        class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm" />
                                    @error('temperature') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                @else
                                    <p class="mt-1 text-zinc-900 dark:text-white">{{ $temperature ?: 'Not recorded' }}</p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Heart Rate (bpm)</label>
                                @if ($editMode)
                                    <flux:input type="number" wire:model="heart_rate" 
                                        class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm" />
                                    @error('heart_rate') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                @else
                                    <p class="mt-1 text-zinc-900 dark:text-white">{{ $heart_rate ?: 'Not recorded' }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Weight (kg)</label>
                                @if ($editMode)
                                    <flux:input type="number" step="0.1" wire:model="weight" 
                                        class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm" />
                                    @error('weight') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                @else
                                    <p class="mt-1 text-zinc-900 dark:text-white">{{ $weight ?: 'Not recorded' }}</p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Height (cm)</label>
                                @if ($editMode)
                                    <flux:input type="number" wire:model="height" 
                                        class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm" />
                                    @error('height') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                @else
                                    <p class="mt-1 text-zinc-900 dark:text-white">{{ $height ?: 'Not recorded' }}</p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Allergies</label>
                                @if ($editMode)
                                    <flux:input wire:model="allergies" 
                                        class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm" />
                                    @error('allergies') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                @else
                                    <p class="mt-1 text-zinc-900 dark:text-white">{{ $allergies ?: 'No allergies recorded' }}</p>
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Lab Results</label>
                            @if ($editMode)
                                <flux:textarea wire:model="lab_results" rows="4" 
                                    class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm" />
                                @error('lab_results') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            @else
                                <p class="mt-1 text-zinc-900 dark:text-white">{{ $lab_results ?: 'No lab results available' }}</p>
                            @endif
                        </div>

                        @if ($editMode)
                            <div class="flex justify-end">
                                <flux:button type="submit"
                                    class="px-6 py-2 !bg-green-600 !text-white rounded-lg hover:!bg-green-700 transition">
                                    Save Changes
                                </flux:button>
                            </div>
                        @endif
                    </form>
                </div>
            @else
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-8 text-center">
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-white mb-4">Appointment Not Found</h2>
                    <p class="text-zinc-600 dark:text-zinc-300 mb-6">The appointment you're looking for doesn't exist or you don't have permission to view it.</p>
                    <a href="{{ route('patient.dashboard') }}" 
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        Back to Dashboard
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
