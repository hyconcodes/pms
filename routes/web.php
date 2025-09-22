<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'role:super-admin', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');
    Volt::route('settings/specializations', 'settings.specializations')->middleware('role:doctor')->name('specializations.edit');

    // Role Management Routes - Only accessible by super-admin
    Route::middleware(['role:super-admin'])->group(function () {
        // Role management using Volt component
        Volt::route('/role-management', 'admin.roles')
            ->name('role-management');
        Volt::route('/staffs-management-sys', 'admin.staffs')
            ->name('staff.sys');
    });
    Route::middleware(['role:patient'])->group(function () {
        Volt::route('/patient-dashboard', 'patients.dashboard')
            ->name('patient.dashboard');
        });
        Volt::route('/patient-management-board', 'admin.patients')
        ->middleware(['permission:view.patients|create.patients|edit.patients|delete.patients|view.medical.records'])
        ->name('admin.patients');
        Volt::route('/medical-records-board', 'admin.medical-records')
        ->middleware(['permission:view.medical.records|create.medical.records|edit.medical.records|delete.medical.records'])
        ->name('admin.medical-records');
        Volt::route('/admin-dashboard', 'admin.dashboard')
            ->name('admin.dashboard');
        Volt::route('/specialization-management-board', 'admin.specializations')
        ->middleware(['permission:view.specializations|create.specializations|edit.specializations|delete.specializations'])
        ->name('admin.specializations');
});

require __DIR__ . '/auth.php';
