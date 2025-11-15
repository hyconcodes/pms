<?php

namespace App\Mail;

use App\Models\MedicalRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentBooked extends Mailable
{
    use Queueable, SerializesModels;

    public MedicalRecord $appointment;

    public function __construct(MedicalRecord $appointment)
    {
        $this->appointment = $appointment->load(['doctor', 'patient']);
    }

    public function build(): self
    {
        return $this->subject('Appointment Confirmation')
            ->view('emails.appointment-booked')
            ->with([
                'appointment' => $this->appointment,
            ]);
    }
}