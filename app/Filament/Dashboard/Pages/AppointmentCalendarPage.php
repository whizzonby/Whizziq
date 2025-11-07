<?php

namespace App\Filament\Dashboard\Pages;

use App\Models\Appointment;
use BackedEnum;
use Filament\Pages\Page;

class AppointmentCalendarPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar-days';

    protected string $view = 'filament.dashboard.pages.appointment-calendar-page';

    protected static ?string $navigationLabel = 'Appointment Calendar';

    public static function getNavigationGroup(): ?string
    {
        return 'Booking';
    }

    //protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Appointment Calendar';


    public function getAppointmentsForCalendar()
    {
        return Appointment::where('user_id', auth()->id())
            ->with(['contact', 'appointmentType'])
            ->get()
            ->map(function($appointment) {
                return [
                    'id' => $appointment->id,
                    'title' => $appointment->title,
                    'start' => $appointment->start_datetime->toIso8601String(),
                    'end' => $appointment->end_datetime->toIso8601String(),
                    'backgroundColor' => $this->getEventColor($appointment->status),
                    'borderColor' => $this->getEventColor($appointment->status),
                    'extendedProps' => [
                        'attendee' => $appointment->attendee_name,
                        'email' => $appointment->attendee_email,
                        'phone' => $appointment->attendee_phone,
                        'status' => $appointment->status,
                        'type' => $appointment->appointmentType?->name ?? 'General',
                        'location' => $appointment->location,
                        'meeting_url' => $appointment->meeting_url,
                    ],
                ];
            });
    }

    protected function getEventColor(string $status): string
    {
        return match($status) {
            'confirmed' => '#10b981', // green
            'scheduled' => '#f59e0b', // amber
            'completed' => '#3b82f6', // blue
            'cancelled' => '#ef4444', // red
            'no_show' => '#6b7280', // gray
            default => '#6b7280',
        };
    }

    public function getHeading(): string
    {
        return 'Appointment Calendar';
    }

    public function getSubheading(): ?string
    {
        return 'View and manage all your appointments in calendar view';
    }
}
