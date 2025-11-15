<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable // implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'matric_no',
        'phone',
        'gender',
        'address',
        'date_of_birth',
        'emergency_contact',
        'specialization_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $with = ['specializations'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    // add specializations relationship
    public function specializations()
    {
        return $this->belongsToMany(Specialization::class, 'staff_specializations', 'user_id', 'specialization_id');
    }

    public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }

    public function patientAppointments()
    {
        return $this->hasMany(MedicalRecord::class, 'patient_id');
    }

    public function patientRecords()
    {
        return $this->hasMany(MedicalRecord::class, 'patient_id');
    }
}
