<?php

use Livewire\Volt\Component;
use App\Models\MedicalRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

new class extends Component {
    public $lastAppointment;
    public $appointments;
    public $showAll = false;

    public function mount()
    {
        try {
            $this->loadLastAppointment();
            $this->loadAppointments();
        } catch (\Exception $e) {
            Log::error('Failed to initialize doctor dashboard', [
                'doctor_id' => auth()->id(),
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
                ->where('doctor_id', auth()->id())
                ->where('status', 'pending')
                ->orderBy('appointment_date', 'desc')
                ->orderBy('appointment_time', 'desc')
                ->first();
        } catch (QueryException $e) {
            Log::error('Database error while loading last appointment', [
                'doctor_id' => auth()->id(),
                'error' => $e->getMessage(),
                'query' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);
            session()->flash('error', 'Unable to load your appointment data. Our team has been notified.');
            $this->lastAppointment = null;
        } catch (\Exception $e) {
            Log::error('Unexpected error while loading last appointment', [
                'doctor_id' => auth()->id(),
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
                ->where('doctor_id', auth()->id())
                ->where('status', 'pending')
                ->orderBy('appointment_date', 'desc')
                ->orderBy('appointment_time', 'desc')
                ->get();
        } catch (QueryException $e) {
            Log::error('Database error while loading appointments', [
                'doctor_id' => auth()->id(),
                'error' => $e->getMessage(),
                'query' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);
            session()->flash('error', 'Unable to load your appointments. Our team has been notified.');
            $this->appointments = collect();
        } catch (\Exception $e) {
            Log::error('Unexpected error while loading appointments', [
                'doctor_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'An unexpected error occurred. Please try again later.');
            $this->appointments = collect();
        }
    }
}; ?>

<div class="min-h-screen p-4 sm:p-6 lg:p-8">
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
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
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Doctor Dashboard</h1>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Quick Stats -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold mb-4 text-zinc-800 dark:text-white">Quick Stats</h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-600 dark:text-zinc-300">Lastest Appointment</span>
                        <span class="text-lg font-bold text-green-600">
                            {{ $lastAppointment ? \Carbon\Carbon::parse($lastAppointment->appointment_date)->format('M d') : 'None' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-600 dark:text-zinc-300">View All</span>
                        <flux:button href="{{ route('doctor.view.all.appointment') }}" size="sm"
                            class="px-2 py-1 text-xs font-medium !text-green-600 border !border-green-600 rounded hover:!bg-green-600 hover:!text-white transition">
                            Appointments
                        </flux:button>
                    </div>
                </div>
            </div>

            <!-- Last Appointment -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6 col-span-1 md:col-span-2">
                <h2 class="text-xl font-semibold mb-4 text-zinc-800 dark:text-white">Lastest Appointment</h2>
                <div class="overflow-x-auto">
                    @if ($lastAppointment)
                        <div
                            class="border dark:border-zinc-700 rounded-lg p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-medium text-zinc-900 dark:text-white">
                                        {{ $lastAppointment->patient->name }}</h3>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-300">
                                        {{ $lastAppointment->specialty }}
                                    </p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-300 mt-1">
                                        {{ $lastAppointment->reason_for_visit }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($lastAppointment->appointment_date)->format('M d, Y') }}
                                    </p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-300">
                                        {{ ucfirst($lastAppointment->appointment_time) }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-2">
                                <span
                                    class="px-2 py-1 text-xs rounded-full {{ $lastAppointment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($lastAppointment->status === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">
                                    {{ ucfirst($lastAppointment->status) }}
                                </span>
                            </div>
                        </div>
                    @else
                        <p class="text-zinc-600 dark:text-zinc-300">No appointments found</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Appointments Table -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold mb-4 text-zinc-800 dark:text-white">Recent Appointments</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead>
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                APID</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Patient</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Date</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Time</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Status</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($appointments as $appointment)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $appointment->apid ?? 'N/A' }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $appointment->patient->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ ucfirst($appointment->appointment_time) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span
                                        class="px-2 py-1 text-xs rounded-full {{ $appointment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($appointment->status === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">
                                        {{ ucfirst($appointment->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <flux:button href="{{ route('doctor.update.appointment', $appointment->id) }}"
                                        size="sm"
                                        class="px-2 py-1 text-xs font-medium !text-blue-600 border !border-blue-600 rounded hover:!bg-blue-600 hover:!text-white transition">
                                        Record
                                    </flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"
                                    class="px-6 py-4 text-center text-sm text-zinc-600 dark:text-zinc-300">
                                    No appointments found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
