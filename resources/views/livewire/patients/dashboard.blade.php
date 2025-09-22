<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Specialization;
use App\Models\MedicalRecord;

new class extends Component {
    public $doctor_id;
    public $specialty;
    public $reason_for_visit;
    public $doctors;
    public $specialties;

    public function mount()
    {
        $this->doctors = User::role('doctor')->get();
        $this->specialties = Specialization::all();
    }

    public function bookAppointment()
    {
        $this->validate([
            'doctor_id' => 'required|exists:users,id',
            'specialty' => 'required|exists:specializations,name',
            'reason_for_visit' => 'required|string'
        ]);

        MedicalRecord::create([
            'patient_id' => auth()->id(),
            'doctor_id' => $this->doctor_id,
            'reason_for_visit' => $this->reason_for_visit,
            'specialty' => $this->specialty,
            'status' => 'pending'
        ]);

        session()->flash('message', 'Appointment booked successfully!');
        $this->reset(['doctor_id', 'specialty', 'reason_for_visit']);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl p-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Patient Dashboard</h1>
    
    <div class="grid auto-rows-min gap-6 md:grid-cols-3">
        <!-- Upcoming Appointments -->
        <div class="flex flex-col rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
            <h2 class="mb-3 text-lg font-semibold text-gray-700 dark:text-gray-200">Upcoming Appointments</h2>
            <div class="flex-1 space-y-2">
                @foreach(auth()->user()->patientAppointments()->where('status', 'pending')->get() as $appointment)
                    <div class="p-3 border rounded-lg">
                        <p class="font-medium">Dr. {{ $appointment->doctor->name }}</p>
                        <p class="text-sm text-gray-600">{{ $appointment->specialty }}</p>
                        <p class="text-sm text-gray-500">Status: {{ ucfirst($appointment->status) }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Medical Records -->
        <div class="flex flex-col rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
            <h2 class="mb-3 text-lg font-semibold text-gray-700 dark:text-gray-200">Medical Records</h2>
            <div class="flex-1 space-y-2">
                @foreach(auth()->user()->patientRecords as $record)
                    <div class="p-3 border rounded-lg">
                        <p class="font-medium">{{ $record->specialty }}</p>
                        <p class="text-sm text-gray-600">{{ Str::limit($record->reason_for_visit, 50) }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Billing & Payments -->
        <div class="flex flex-col rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
            <h2 class="mb-3 text-lg font-semibold text-gray-700 dark:text-gray-200">Billing & Payments</h2>
            <div class="flex-1 space-y-2">
                <!-- Add billing information here -->
            </div>
        </div>
    </div>

    <div class="grid flex-1 gap-6 md:grid-cols-7">
        <!-- Main Content Area (4 columns) -->
        <div class="md:col-span-5 rounded-xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200">Schedule an Appointment</h2>
            </div>
            <div class="space-y-4">
                <form wire:submit="bookAppointment" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Select Doctor</label>
                        <select wire:model="doctor_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Choose a doctor</option>
                            @foreach($doctors as $doctor)
                                <option value="{{ $doctor->id }}">Dr. {{ $doctor->name }}</option>
                            @endforeach
                        </select>
                        @error('doctor_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Specialty</label>
                        <select wire:model="specialty" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Choose specialty</option>
                            @foreach($specialties as $specialty)
                                <option value="{{ $specialty->name }}">{{ $specialty->name }}</option>
                            @endforeach
                        </select>
                        @error('specialty') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reason for Visit</label>
                        <textarea wire:model="reason_for_visit" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        @error('reason_for_visit') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end">
                        <flux:button type="submit" class="rounded-lg !bg-green-600 px-4 py-2 !text-white hover:!bg-green-700 transition duration-150 ease-in-out">
                            Book Appointment
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar (2 columns) -->
        <div class="md:col-span-2 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
            <h2 class="mb-3 text-lg font-semibold text-gray-700 dark:text-gray-200">Quick Actions</h2>
            <div class="space-y-2">
                <!-- Add quick action buttons or information here -->
            </div>
        </div>
    </div>
</div>
