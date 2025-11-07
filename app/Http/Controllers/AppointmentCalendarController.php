<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\CalendarFileService;
use Illuminate\Http\Response;

class AppointmentCalendarController extends Controller
{
    protected CalendarFileService $calendarService;

    public function __construct(CalendarFileService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    /**
     * Download ICS calendar file for an appointment
     *
     * @param string $token
     * @return Response
     */
    public function downloadICS(string $token)
    {
        $appointment = Appointment::where('confirmation_token', $token)->firstOrFail();

        $icsContent = $this->calendarService->generateICS($appointment);
        $filename = $this->calendarService->getFilename($appointment);

        return response($icsContent)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
