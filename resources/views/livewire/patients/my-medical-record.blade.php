<?php

use Livewire\Volt\Component;
use App\Models\MedicalRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

new class extends Component {
    public $user;
    public $appointments;

    public function mount()
    {
        $this->loadUser();
        $this->loadAppointments();
    }

    public function loadUser()
    {
        try {
            $this->user = Auth::user();
        } catch (\Exception $e) {
            Log::error('Failed to load user', [
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to load user details.');
        }
    }

    public function loadAppointments()
    {
        try {
            $this->appointments = MedicalRecord::with('doctor')
                ->where('patient_id', Auth::id())
                ->latest()
                ->limit(1)
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to load appointments', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to load appointment history.');
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
        @if ($user)
            <!-- User Profile Card -->
            <div class="bg-white/80 dark:bg-green-900/30 backdrop-blur-xl rounded-2xl shadow-lg border border-green-200/60 dark:border-green-700/40 overflow-hidden mb-8">
                <!-- Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-green-500 to-emerald-500">
                    <h1 class="text-xl font-bold text-white">My Profile</h1>
                    <p class="text-xs text-green-100 mt-1">Matric: {{ $user->matric_no }}</p>
                </div>
                <!-- Horizontal Card -->
                <div class="p-6 flex flex-col md:flex-row gap-6 items-start">
                    <!-- Avatar / Icon -->
                    <div class="flex-shrink-0">
                        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center text-white text-2xl font-bold">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    </div>
                    <!-- Details -->
                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-green-600 dark:text-green-400">Name</p>
                            <p class="mt-1 text-zinc-800 dark:text-zinc-100 font-semibold">{{ $user->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-green-600 dark:text-green-400">Email</p>
                            <p class="mt-1 text-zinc-800 dark:text-zinc-100 font-semibold truncate">{{ $user->email }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-green-600 dark:text-green-400">Matric No</p>
                            <p class="mt-1 text-zinc-800 dark:text-zinc-100 font-semibold">{{ $user->matric_no ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-green-600 dark:text-green-400">Phone</p>
                            <p class="mt-1 text-zinc-800 dark:text-zinc-100 font-semibold">{{ $user->phone ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-green-600 dark:text-green-400">Gender</p>
                            <p class="mt-1 text-zinc-800 dark:text-zinc-100 font-semibold capitalize">{{ $user->gender ?: '—' }}</p>
                        </div>
                        <div class="sm:col-span-2 lg:col-span-2">
                            <p class="text-xs uppercase tracking-wide text-green-600 dark:text-green-400">Member Since</p>
                            <p class="mt-1 text-zinc-800 dark:text-zinc-100 font-semibold">
                                {{ $user->created_at ? \Carbon\Carbon::parse($user->created_at)->format('D, M d, Y') : '—' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Appointment Vitals -->
            @if ($appointments->isNotEmpty())
                @php
                    $latest = $appointments->first();
                @endphp
                <div class="bg-white/70 dark:bg-zinc-800/70 backdrop-blur-xl rounded-2xl shadow-xl border border-zinc-200/50 dark:border-zinc-700/50 overflow-hidden">
                    <div class="px-6 py-5 border-b border-zinc-200/60 dark:border-zinc-700/60">
                        <h2 class="text-xl font-bold text-zinc-800 dark:text-zinc-100">My Medical Record</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                            Reference #{{ $latest->id }} · {{ \Carbon\Carbon::parse($latest->appointment_date)->format('D, M d, Y') }}
                        </p>
                    </div>
                    <div class="p-6 grid gap-6">
                        <div class="grid md:grid-cols-3 gap-4">
                            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Blood Pressure</p>
                                <p class="mt-1 text-zinc-800 dark:text-zinc-100">{{ $latest->blood_pressure ?: '—' }}</p>
                            </div>
                            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Temperature (°C)</p>
                                <p class="mt-1 text-zinc-800 dark:text-zinc-100">{{ $latest->temperature ?: '—' }}</p>
                            </div>
                            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Heart Rate (bpm)</p>
                                <p class="mt-1 text-zinc-800 dark:text-zinc-100">{{ $latest->heart_rate ?: '—' }}</p>
                            </div>
                        </div>
                        <div class="grid md:grid-cols-3 gap-4">
                            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Weight (kg)</p>
                                <p class="mt-1 text-zinc-800 dark:text-zinc-100">{{ $latest->weight ?: '—' }}</p>
                            </div>
                            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Height (cm)</p>
                                <p class="mt-1 text-zinc-800 dark:text-zinc-100">{{ $latest->height ?: '—' }}</p>
                            </div>
                            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-4">
                                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Allergies</p>
                                <p class="mt-1 text-zinc-800 dark:text-zinc-100">{{ $latest->allergies ?: '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white/70 dark:bg-zinc-800/70 backdrop-blur-xl rounded-2xl shadow-xl border border-zinc-200/50 dark:border-zinc-700/50 p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <h3 class="mt-4 text-lg font-semibold text-zinc-800 dark:text-zinc-100">No appointments found</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">There are no recorded appointments for you.</p>
                </div>
            @endif
        @else
            <div class="bg-white/80 dark:bg-green-900/30 backdrop-blur-xl rounded-2xl shadow-lg border border-green-200/60 dark:border-green-700/40 p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-green-400 dark:text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-zinc-800 dark:text-zinc-100">User not found</h3>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">It may have been removed or you don’t have permission to view it.</p>
            </div>
        @endif
    </div>
</div>
