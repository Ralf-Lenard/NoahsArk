<?php

namespace App\Http\Controllers;

use App\Events\AdoptionAppointmentScheduled;
use App\Models\AdoptionAppointment;
use App\Models\AdoptionRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdoptionAppointmentController extends Controller
{
    public function index()
    {
        // Get all adoption appointments and eager load user and animal
        $appointments = AdoptionAppointment::with([
            'adoptionRequest.user',
            'adoptionRequest.animal'
        ])->get();
    
        // Map the appointments to extract only necessary data
        $mappedAppointments = $appointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'date' => $appointment->appointment_date,
                'time' => $appointment->appointment_time,
                'status' => $appointment->status,
                'notes' => $appointment->notes,
                'rejection_reason' => $appointment->rejection_reason,
                'user' => [
                    'id' => $appointment->adoptionRequest->user->id ?? null,
                    'name' => $appointment->adoptionRequest->user->name ?? 'Unknown User',
                    'email' => $appointment->adoptionRequest->user->email ?? 'Unknown Email',
                ],
                'animal' => [
                    'id' => $appointment->adoptionRequest->animal->id ?? null,
                    'name' => $appointment->adoptionRequest->animal->name ?? 'Unknown Animal',
                    'breed' => $appointment->adoptionRequest->animal->breed ?? '',
                ]
            ];
        });

    
    
        // Get approved adoption requests that don't have an appointment yet
        $approvedRequests = AdoptionRequest::with('user', 'animal')
            ->where('status', 'approved')
            ->doesntHave('appointment')
            ->get();
    
        // Optionally map approvedRequests if needed
        $mappedApprovedRequests = $approvedRequests->map(function ($request) {
            return [
                'id' => $request->id,
                'user' => [
                    'id' => $request->user->id ?? null,
                    'name' => $request->user->name ?? 'Unknown User',
                ],
                'animal' => [
                    'id' => $request->animal->id ?? null,
                    'name' => $request->animal->name ?? 'Unknown Animal',
                ]
            ];
        });
    
        return Inertia::render('Staff/AdoptionAppointments', [
            'appointments' => $mappedAppointments,
            'approvedRequests' => $mappedApprovedRequests
        ]);
    }
    
    
    public function store(Request $request)
    {
        $request->validate([
            'adoption_request_id' => 'required|exists:adoption_requests,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
            'notes' => 'nullable|string',
        ]);
    
        $appointment = AdoptionAppointment::create([
            'adoption_request_id' => $request->adoption_request_id,
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $request->appointment_time,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);
    
        // Fire the event
        event(new AdoptionAppointmentScheduled($appointment));
    
        return redirect()->route('appointments.index')->with('success', 'Virtual appointment scheduled successfully.');
    }

    public function update(Request $request, AdoptionAppointment $appointment)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed',
        ]);

        $appointment->update(['status' => $request->status]);

        return back()->with('success', 'Appointment status updated.');
    }

    public function destroy(AdoptionAppointment $appointment)
    {
        $appointment->delete();

        return back()->with('success', 'Appointment deleted.');
    }
}
