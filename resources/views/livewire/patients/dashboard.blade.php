<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Specialization;
use App\Models\MedicalRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

new class extends Component {
    public $doctor_id;
    public $specialty;
    public $reason_for_visit = ''; // Initialize with empty string
    public $appointment_date;
    public $preferred_time = 'morning';
    public $visit_type = 'in-person';
    public $doctors;
    public $filteredDoctors = [];
    public $specialties;
    public $upcomingAppointments;
    public $medicalRecords;
    public $showModal = false;

    // Rest of the component code remains the same
    public function mount()
    {
        try {
            $this->doctors = User::role('doctor')->get();
            $this->specialties = Specialization::all();
            $this->refreshDashboardData();
        } catch (\Exception $e) {
            Log::error('Failed to initialize patient dashboard', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('error', 'Failed to load dashboard data. Please try again later.');
        }
    }

    public function updatedSpecialty($value)
    {
        try {
            // Reset doctor selection when specialty changes
            $this->doctor_id = '';

            if ($value) {
                // Get specialization ID for the selected specialty name
                $specialization = Specialization::where('name', $value)->first();

                if ($specialization) {
                    // Filter doctors based on specialization_id in users table
                    $this->filteredDoctors = User::role('doctor')->where('specialization_id', $specialization->id)->get();
                } else {
                    $this->filteredDoctors = [];
                }
            } else {
                $this->filteredDoctors = [];
            }
        } catch (\Exception $e) {
            Log::error('Error updating specialty selection', [
                'user_id' => auth()->id(),
                'specialty' => $value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->filteredDoctors = [];
        }
    }

    protected function refreshDashboardData()
    {
        try {
            $this->upcomingAppointments = MedicalRecord::with('doctor')
                ->where('patient_id', auth()->id())
                // ->where('appointment_date', '>=', now()->startOfDay())
                // ->where('appointment_date', '<=', now()->addDays(30))
                ->where('appointment_date', '>=', now())
                ->where('appointment_date', '<=', now()->addDays(30))
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->get();

            $this->medicalRecords = MedicalRecord::with('doctor')
                ->where('patient_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        } catch (QueryException $e) {
            Log::error('Database error while refreshing dashboard data', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'query' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);

            session()->flash('error', 'Unable to load your dashboard data. Our team has been notified.');
            $this->upcomingAppointments = collect();
            $this->medicalRecords = collect();
        } catch (\Exception $e) {
            Log::error('Unexpected error while refreshing dashboard data', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('error', 'An unexpected error occurred. Please try again later.');
            $this->upcomingAppointments = collect();
            $this->medicalRecords = collect();
        }
    }

    public function openModal()
    {
        try {
            $this->showModal = true;
        } catch (\Exception $e) {
            Log::error('Error opening modal', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function closeModal()
    {
        try {
            $this->showModal = false;
            $this->reset(['doctor_id', 'specialty', 'reason_for_visit', 'appointment_date', 'preferred_time', 'visit_type']);
            $this->filteredDoctors = [];
        } catch (\Exception $e) {
            Log::error('Error closing modal', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function bookAppointment()
    {
        try {
            // Check if user already has 2 or more pending appointments
            $pendingAppointmentsCount = MedicalRecord::where('patient_id', auth()->id())
                ->where('status', 'pending')
                ->count();

            if ($pendingAppointmentsCount >= 2) {
                session()->flash('error', 'You already have 2 pending appointments. Please wait for them to be processed before booking more.');
                return;
            }

            // Log the reason_for_visit value before validation
            Log::info('Reason for visit before validation:', ['reason_for_visit' => $this->reason_for_visit]);

            $validatedData = $this->validate([
                'doctor_id' => 'required|exists:users,id',
                'specialty' => 'required|exists:specializations,name',
                // 'reason_for_visit' => 'required|string|min:1',
                'appointment_date' => 'required|date|after_or_equal:today',
                'preferred_time' => 'required|in:morning,afternoon',
                'visit_type' => 'required|in:in-person,virtual',
            ]);

            // Verify doctor has the correct specialization
            $specialization = Specialization::where('name', $this->specialty)->first();
            $doctorHasSpecialty = User::role('doctor')->where('id', $this->doctor_id)->where('specialization_id', $specialization->id)->exists();

            if (!$doctorHasSpecialty) {
                Log::warning('Doctor specialty mismatch', [
                    'user_id' => auth()->id(),
                    'doctor_id' => $this->doctor_id,
                    'specialty' => $this->specialty,
                ]);
                session()->flash('error', 'The selected doctor does not practice the selected specialty.');
                return;
            }

            $isDoctorAvailable = !MedicalRecord::where('doctor_id', $this->doctor_id)->where('appointment_date', $this->appointment_date)->where('appointment_time', $this->preferred_time)->exists();

            if (!$isDoctorAvailable) {
                Log::warning('Doctor unavailable for requested time slot', [
                    'user_id' => auth()->id(),
                    'doctor_id' => $this->doctor_id,
                    'appointment_date' => $this->appointment_date,
                    'preferred_time' => $this->preferred_time,
                ]);
                session()->flash('error', 'The selected doctor is not available at the chosen time.');
                return;
            }
            $appointment = MedicalRecord::create([
                'patient_id' => auth()->id(),
                'doctor_id' => $this->doctor_id,
                'reason_for_visit' => trim($this->reason_for_visit),
                'specialty' => $this->specialty,
                'status' => 'pending',
                'appointment_date' => $this->appointment_date,
                'visit_type' => $this->visit_type,
                'appointment_time' => $this->preferred_time,
            ]);

            Log::info('Appointment booked successfully', [
                'appointment_id' => $appointment->id,
                'patient_id' => auth()->id(),
                'doctor_id' => $this->doctor_id,
                'appointment_date' => $this->appointment_date,
            ]);

            $this->refreshDashboardData();
            session()->flash('message', 'Appointment booked successfully!');
            session()->flash('alert-type', 'success');
            $this->closeModal();
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error while booking appointment', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'validation_errors' => $e->errors(),
            ]);
            throw $e;
        } catch (QueryException $e) {
            Log::error('Database error while booking appointment', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'query' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);
            session()->flash('error', 'A database error occurred while booking your appointment.');
        } catch (\Exception $e) {
            Log::error('Failed to book appointment', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'An unexpected error occurred while booking your appointment.');
        }
    }
}; ?>

<main>
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
                @if (session('details'))
                    <div class="mt-2 text-sm">
                        {{ session('details') }}
                    </div>
                @endif
            </div>
        @endif

        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Patient Dashboard</h1>
                <flux:button wire:click="openModal"
                    class="mt-4 md:mt-0 px-6 py-2 !bg-green-600 !text-white rounded-lg hover:!bg-green-700 transition">
                    Book New Appointment
                </flux:button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Quick Stats -->
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold mb-4 text-zinc-800 dark:text-white">Quick Stats</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-zinc-600 dark:text-zinc-300">Upcoming Appointments</span>
                            <span class="text-lg font-bold text-green-600">{{ $upcomingAppointments->count() }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-zinc-600 dark:text-zinc-300">Recent Records</span>
                            <span class="text-lg font-bold text-green-600">{{ $medicalRecords->count() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6 col-span-1 md:col-span-2">
                    <h2 class="text-xl font-semibold mb-4 text-zinc-800 dark:text-white">Upcoming Appointments</h2>
                    <div class="overflow-x-auto">
                        @if ($upcomingAppointments->count() > 0)
                            <div class="space-y-4">
                                @foreach ($upcomingAppointments as $appointment)
                                    <div
                                        class="border dark:border-zinc-700 rounded-lg p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="font-medium text-zinc-900 dark:text-white">Dr.
                                                    {{ $appointment->doctor->name }}</h3>
                                                <p class="text-sm text-zinc-600 dark:text-zinc-300">
                                                    {{ $appointment->specialty }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                                    {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y') }}
                                                </p>
                                                <p class="text-sm text-zinc-600 dark:text-zinc-300">
                                                    {{ ucfirst($appointment->appointment_time) }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <span
                                                class="px-2 py-1 text-xs rounded-full {{ $appointment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($appointment->status === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">
                                                {{ ucfirst($appointment->status) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-zinc-600 dark:text-zinc-300">No upcoming appointments</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Recent Medical Records -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6 mt-6">
                <h2 class="text-xl font-semibold mb-4 text-zinc-800 dark:text-white">Recent Appointments</h2>
                @if ($medicalRecords->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                            <thead>
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                        Date</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                        Doctor</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                        Specialty</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                        Status</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach ($medicalRecords as $record)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($record->created_at)->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-white">
                                            Dr. {{ $record->doctor->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-white">
                                            {{ $record->specialty }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="px-2 py-1 text-xs rounded-full {{ $record->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($record->status === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">
                                                {{ ucfirst($record->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('patient.appointment.details', $record->id) }}" 
                                               class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-zinc-600 dark:text-zinc-300">No medical records found</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Book Appointment Modal -->
    <div x-data x-show="$wire.showModal"
        class="fixed inset-0 bg-zinc-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-transition>
        <div class="relative top-20 mx-auto p-5 border max-w-4xl shadow-lg rounded-md bg-white dark:bg-zinc-800">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-white">Book New Appointment</h3>

                <flux:button wire:click="closeModal" class="text-zinc-400 hover:text-zinc-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </flux:button>
            </div>
            
            <!-- Success and Error Messages -->
            @if (session()->has('message'))
                <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('message') }}
                </div>
            @endif
            
            @if (session()->has('error'))
                <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <form wire:submit="bookAppointment" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <!-- Specialty -->
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Specialty <span class="text-red-500">*</span>
                            </label>
                            <flux:select wire:model.live="specialty"
                                class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="">Select specialty first</option>
                                @foreach ($specialties as $spec)
                                    <option value="{{ $spec->name }}">{{ $spec->name }}</option>
                                @endforeach
                            </flux:select>
                            @error('specialty')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Doctor <span class="text-red-500">*</span>
                            </label>
                            <flux:select wire:model="doctor_id"
                                class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="">Select doctor</option>
                                @foreach ($filteredDoctors as $doctor)
                                    <option value="{{ $doctor->id }}">Dr. {{ $doctor->name }}</option>
                                @endforeach
                            </flux:select>
                            @error('doctor_id')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Appointment Date <span class="text-red-500">*</span>
                            </label>
                            <flux:input type="date" wire:model="appointment_date"
                                class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                min="{{ date('Y-m-d') }}" />
                            @error('appointment_date')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Reason for Visit <span class="text-red-500">*</span>
                            </label>
                            <flux:textarea wire:model="reason_for_visit" rows="3"
                                class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                placeholder="Please describe your reason for visit"></flux:textarea>
                            @error('reason_for_visit')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Preferred Time <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1">
                                <label class="inline-flex items-center mr-4">
                                    <flux:radio wire:model="preferred_time" value="morning" class="text-green-600" />
                                    <span class="ml-2 text-zinc-700 dark:text-zinc-300">Morning</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <flux:radio wire:model="preferred_time" value="afternoon"
                                        class="text-green-600" />
                                    <span class="ml-2 text-zinc-700 dark:text-zinc-300">Afternoon</span>
                                </label>
                            </div>
                            @error('preferred_time')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Visit Type <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1">
                                <label class="inline-flex items-center mr-4">
                                    <flux:radio wire:model="visit_type" value="in-person" class="text-green-600" />
                                    <span class="ml-2 text-zinc-700 dark:text-zinc-300">In-Person</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <flux:radio wire:model="visit_type" value="virtual" class="text-green-600" />
                                    <span class="ml-2 text-zinc-700 dark:text-zinc-300">Virtual</span>
                                </label>
                            </div>
                            @error('visit_type')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button type="button" wire:click="closeModal"
                        class="px-4 py-2 text-sm font-medium text-zinc-700 bg-zinc-100 hover:bg-zinc-200 rounded-md">
                        Cancel
                    </flux:button>
                    <flux:button type="submit"
                        class="px-4 py-2 text-sm font-medium !text-white !bg-green-600 hover:!bg-green-700 rounded-md">
                        Book Appointment
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</main>
