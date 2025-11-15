<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Confirmation</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1f2937; }
        .container { max-width: 600px; margin: 0 auto; padding: 24px; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; }
        .title { font-size: 20px; font-weight: 700; color: #065f46; }
        .section { margin-top: 16px; }
        .label { font-weight: 600; color: #374151; }
        .value { color: #111827; }
        .footer { margin-top: 24px; font-size: 12px; color: #6b7280; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 9999px; background: #ecfccb; color: #166534; font-size: 12px; font-weight: 600; }
    </style>
    </head>
<body>
    <div class="container">
        <div class="title">Your Appointment is Confirmed</div>
        <div class="section">
            <p class="value">Hello {{ $appointment->patient->name }},</p>
            <p class="value">Thanks for booking an appointment. Here are the details:</p>
        </div>
        <div class="section">
            <p><span class="label">Appointment ID:</span> <span class="value">{{ $appointment->apid }}</span></p>
            <p><span class="label">Doctor:</span> <span class="value">Dr. {{ $appointment->doctor->name }}</span></p>
            <p><span class="label">Specialty:</span> <span class="value">{{ $appointment->specialty }}</span></p>
            <p><span class="label">Date:</span> <span class="value">{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y') }}</span></p>
            <p><span class="label">Time:</span> <span class="value">{{ ucfirst($appointment->appointment_time) }}</span></p>
            @if($appointment->reason_for_visit)
                <p><span class="label">Reason for visit:</span> <span class="value">{{ $appointment->reason_for_visit }}</span></p>
            @endif
            <p class="pill">Status: {{ ucfirst($appointment->status) }}</p>
        </div>
        <div class="section">
            <p class="value">Please arrive 10 minutes early and bring any relevant medical documents.</p>
            <p class="value">If you need to modify or cancel, reply to this email or visit your dashboard.</p>
        </div>
        <div class="footer">
            <p>Sent by {{ config('app.name') }}</p>
            <p>{{ config('app.url') }}</p>
        </div>
    </div>
</body>
</html>