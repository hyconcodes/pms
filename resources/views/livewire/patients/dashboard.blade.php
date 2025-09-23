<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Specialization;
use App\Models\MedicalRecord;
use Illuminate\Support\Str;

new class extends Component {
    public $doctor_id;
    public $specialty;
    public $reason_for_visit;
    public $appointment_date;
    public $preferred_time = 'morning';
    public $visit_type = 'in-person';
    public $doctors;
    public $specialties;
    public $upcomingAppointments;
    public $medicalRecords;
    public $notification = null;

    public function mount()
    {
        $this->doctors = User::role('doctor')->get();
        $this->specialties = Specialization::all();
        $this->refreshDashboardData();
    }

    protected function refreshDashboardData()
    {
        $this->upcomingAppointments = MedicalRecord::with('doctor')
            ->where('patient_id', auth()->id())
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
    }

    public function bookAppointment()
    {
        $validatedData = $this->validate([
            'doctor_id' => 'required|exists:users,id',
            'specialty' => 'required|exists:specializations,name',
            'reason_for_visit' => 'required|string|max:500',
            'appointment_date' => 'required|date|after_or_equal:today',
            'preferred_time' => 'required|in:morning,afternoon',
            'visit_type' => 'required|in:in-person,virtual',
        ]);

        try {
            MedicalRecord::create([
                'patient_id' => auth()->id(),
                'doctor_id' => $this->doctor_id,
                'reason_for_visit' => $this->reason_for_visit,
                'specialty' => $this->specialty,
                'status' => 'pending',
                'appointment_date' => $this->appointment_date,
                'visit_type' => $this->visit_type,
                'appointment_time' => $this->preferred_time,
            ]);

            $this->refreshDashboardData();
            session()->flash('message', 'Appointment booked successfully!');
            session()->flash('alert-type', 'success');

            $this->reset(['doctor_id', 'specialty', 'reason_for_visit', 'appointment_date', 'preferred_time', 'visit_type']);

            $this->notification = [
                'type' => 'success',
                'message' => 'Appointment booked successfully!',
            ];

            $this->dispatch('showNotification', [
                'type' => 'success',
                'message' => 'Appointment booked successfully!',
            ]);
        } catch (\Exception $e) {
            report($e);

            session()->flash('message', 'Failed to book appointment: ' . $e->getMessage());
            session()->flash('alert-type', 'error');

            $this->notification = [
                'type' => 'error',
                'message' => 'Failed to book appointment. Please try again.',
            ];

            $this->dispatch('showNotification', [
                'type' => 'error',
                'message' => 'Failed to book appointment. Please try again.',
            ]);
        }
    }
}; ?>

<main>
    <div class="min-h-screen bg-zinc-50 dark:bg-zinc-900 p-4 sm:p-6 lg:p-8">
        <div x-data="{ showNotification: false }" x-show="showNotification" x-init="@this.on('showNotification', () => { showNotification = true;
            setTimeout(() => { showNotification = false }, 3000) })" class="fixed top-4 right-4 z-50">
            @if (session()->has('message'))
                <div class="mb-4 p-4 rounded-lg shadow-lg transform transition-all duration-300"
                    :class="{ 'translate-x-0': showNotification, 'translate-x-full': !showNotification }"
                    class="{{ session('alert-type') === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ session('message') }}
                </div>
            @endif
        </div>

        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Patient Dashboard</h1>
                <flux:button class="mt-4 md:mt-0 px-6 py-2 !bg-green-600 !text-white rounded-lg hover:!bg-green-700 transition"
                    onclick="document.getElementById('bookAppointmentModal').classList.remove('hidden')">
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
                                                    {{ ucfirst($appointment->appointment_time) }}</p>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <span
                                                class="px-2 py-1 text-xs rounded-full 
                                            {{ $appointment->status === 'pending'
                                                ? 'bg-yellow-100 text-yellow-800'
                                                : ($appointment->status === 'confirmed'
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-red-100 text-red-800') }}">
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
                <h2 class="text-xl font-semibold mb-4 text-zinc-800 dark:text-white">Recent Medical Records</h2>
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
                                                class="px-2 py-1 text-xs rounded-full 
                                            {{ $record->status === 'pending'
                                                ? 'bg-yellow-100 text-yellow-800'
                                                : ($record->status === 'confirmed'
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-red-100 text-red-800') }}">
                                                {{ ucfirst($record->status) }}
                                            </span>
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
    <div id="bookAppointmentModal"
        class="hidden fixed inset-0 bg-zinc-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border max-w-4xl shadow-lg rounded-md bg-white dark:bg-zinc-800">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-white">Book New Appointment</h3>
                <flux:button onclick="document.getElementById('bookAppointmentModal').classList.add('hidden')"
                    class="text-zinc-400 hover:text-zinc-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </flux:button>
            </div>

            <form wire:submit="bookAppointment" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Doctor</label>
                            <flux:select wire:model="doctor_id"
                                class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="">Select a doctor</option>
                                @foreach ($doctors as $doctor)
                                    <option value="{{ $doctor->id }}">Dr. {{ $doctor->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Specialty</label>
                            <flux:select wire:model="specialty"
                                class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="">Select specialty</option>
                                @foreach ($specialties as $specialty)
                                    <option value="{{ $specialty->name }}">{{ $specialty->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Appointment Date</label>
                            <flux:input type="date" wire:model="appointment_date"
                                class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm focus:border-green-500 focus:ring-green-500"/>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Reason for Visit</label>
                            <flux:textarea wire:model="reason_for_visit" rows="3"
                                class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm focus:border-green-500 focus:ring-green-500"/>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Preferred Time</label>
                            <flux:select wire:model="preferred_time"
                                class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="morning">Morning</option>
                                <option value="afternoon">Afternoon</option>
                            </flux:select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Visit Type</label>
                            <flux:select wire:model="visit_type"
                                class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="in-person">In-Person</option>
                                <option value="virtual">Virtual</option>
                            </flux:select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <flux:button type="button"
                        onclick="document.getElementById('bookAppointmentModal').classList.add('hidden')"
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
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('showNotification', (data) => {
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg transform transition-all duration-300 ${
                data.type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
            }`;
                notification.textContent = data.message;
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.remove();
                }, 3000);
            });
        });
    </script>

</main>
