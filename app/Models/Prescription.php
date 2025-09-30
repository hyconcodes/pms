<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    protected $table = 'prescriptions';
    protected $fillable = [
        'appointment_id',
        'medication_id',
        'quantity',
        'instructions',
        'prescribed_date',
        'payment_method',
        'payment_amount',
        'payment_status',
    ];

    public function appointment()
    {
        return $this->belongsTo(MedicalRecord::class, 'appointment_id');
    }

    public function medication()
    {
        return $this->belongsTo(Medication::class, 'medication_id');
    }
}
