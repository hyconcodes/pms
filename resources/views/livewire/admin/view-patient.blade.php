<?php

use Livewire\Volt\Component;
use App\Models\User;
use Illuminate\Support\Facades\Log;

new class extends Component {
    public $patient_id;
    public $patient;

    public function mount($patient_id)
    {
        $this->patient_id = $patient_id;
        $this->loadPatient();
    }

    public function loadPatient()
    {
        try {
            $this->patient = User::role('patient')
                ->where('id', $this->patient_id)
                ->first();
        } catch (\Exception $e) {
            Log::error('Failed to load patient', [
                'patient_id' => $this->patient_id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to load patient details.');
        }
    }
}; ?>

<div class="min-h-screen">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if ($patient)
            <div class="bg-white/80 dark:bg-green-900/30 backdrop-blur-xl rounded-2xl shadow-lg border border-green-200/60 dark:border-green-700/40 overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-green-500 to-emerald-500">
                    <h1 class="text-xl font-bold text-white">Patient Profile</h1>
                    <p class="text-xs text-green-100 mt-1">Matric: {{ $patient->matric_no }}</p>
                </div>

                <!-- Horizontal Card -->
                <div class="p-6 flex flex-col md:flex-row gap-6 items-start">
                    <!-- Avatar / Icon -->
                    <div class="flex-shrink-0">
                        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center text-white text-2xl font-bold">
                            {{ strtoupper(substr($patient->name, 0, 1)) }}
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-green-600 dark:text-green-400">Name</p>
                            <p class="mt-1 text-slate-800 dark:text-slate-100 font-semibold">{{ $patient->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-green-600 dark:text-green-400">Email</p>
                            <p class="mt-1 text-slate-800 dark:text-slate-100 font-semibold truncate">{{ $patient->email }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-green-600 dark:text-green-400">Matric No</p>
                            <p class="mt-1 text-slate-800 dark:text-slate-100 font-semibold">{{ $patient->matric_no ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-green-600 dark:text-green-400">Phone</p>
                            <p class="mt-1 text-slate-800 dark:text-slate-100 font-semibold">{{ $patient->phone ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-green-600 dark:text-green-400">Gender</p>
                            <p class="mt-1 text-slate-800 dark:text-slate-100 font-semibold capitalize">{{ $patient->gender ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-green-600 dark:text-green-400">Emergency Contact</p>
                            <p class="mt-1 text-slate-800 dark:text-slate-100 font-semibold">{{ $patient->emergency_contact ?: '—' }}</p>
                        </div>
                        <div class="sm:col-span-2 lg:col-span-2">
                            <p class="text-xs uppercase tracking-wide text-green-600 dark:text-green-400">Member Since</p>
                            <p class="mt-1 text-slate-800 dark:text-slate-100 font-semibold">
                                {{ $patient->created_at ? \Carbon\Carbon::parse($patient->created_at)->format('D, M d, Y') : '—' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white/80 dark:bg-green-900/30 backdrop-blur-xl rounded-2xl shadow-lg border border-green-200/60 dark:border-green-700/40 p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-green-400 dark:text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-slate-800 dark:text-slate-100">Patient not found</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">It may have been removed or you don’t have permission to view it.</p>
            </div>
        @endif
    </div>
</div>
