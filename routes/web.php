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
        Volt::route('/pharm-management-sys', 'admin.pharmacist')
            ->name('staff.sys');
        Volt::route('/doc-management-sys', 'admin.doctor')
            ->name('staff.doc');
    });
    Route::middleware(['role:patient'])->group(function () {
        Volt::route('/patient/dashboard', 'patients.dashboard')
            ->name('patient.dashboard');
        Volt::route('/patient/appointments/{appointment}', 'patients.view-appointment')
            ->name('patient.appointment.details');
        });
        
    Volt::route('/patient/management-board', 'admin.patients')
        ->middleware(['permission:view.patients|create.patients|edit.patients|delete.patients|view.medical.records'])
        ->name('admin.patients');
    Volt::route('/admin/medical-records-board', 'admin.medical-records')
        ->middleware(['permission:view.medical.records|create.medical.records|edit.medical.records|delete.medical.records'])
        ->name('admin.medical-records');
        // Doc dashboard
    Volt::route('/doctors/dashboard', 'doctors.dashboard')
    ->name('admin.dashboard');
    Volt::route('/admin/specialization-management-board', 'admin.specializations')
    ->middleware(['permission:view.specializations|create.specializations|edit.specializations|delete.specializations'])
    ->name('admin.specializations');
    Volt::route('/doctor/{appointment}/appointments', 'doctors.update-appointments')
        ->name('doctor.update.appointment');
    Volt::route('/doctor/all-appointment', 'doctors.all-appointments')
        ->middleware(['permission:approve.appointments'])
        ->name('doctor.view.all.appointment');
    Volt::route('/doctor/{patient_id}/all-patient', 'admin.view-patient')
        ->middleware(['permission:view.patients'])
        ->name('view.patient');
});

require __DIR__ . '/auth.php';
