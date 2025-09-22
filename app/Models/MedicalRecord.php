<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id', 
        'reason_for_visit',
        'specialty',
        'diagnosis',
        'symptoms',
        'notes',
        'blood_pressure',
        'lab_results',
        'appointment_date',
        'appointment_time',
        'temperature',
        'heart_rate',
        'weight',
        'height',
        'allergies',
        'status'
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'datetime',
        'weight' => 'decimal:2',
        'height' => 'decimal:2'
    ];
}
