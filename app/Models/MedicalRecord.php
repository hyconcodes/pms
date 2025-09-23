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
        'status',
        'visit_type'
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'string',
        'weight' => 'decimal:2',
        'height' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Get the patient that owns the medical record.
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
    
    /**
     * Get the doctor that the appointment is with.
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
