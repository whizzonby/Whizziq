<?php

namespace App\Livewire\Calendar;

use App\Models\Appointment;
use Carbon\Carbon;
use Livewire\Component;

class AppointmentCalendar extends Component
{
    public $year;
    public $month;
    public $selectedDate;
    public $appointments = [];

    public function mount()
    {
        $this->year = now()->year;
        $this->month = now()->month;
        $this->loadAppointments();
    }

    public function previousMonth()
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $this->year = $date->year;
        $this->month = $date->month;
        $this->loadAppointments();
    }

    public function nextMonth()
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->addMonth();
        $this->year = $date->year;
        $this->month = $date->month;
        $this->loadAppointments();
    }

    public function goToToday()
    {
        $this->year = now()->year;
        $this->month = now()->month;
        $this->loadAppointments();
    }

    public function selectDate($date)
    {
        $this->selectedDate = $date;
    }

    public function loadAppointments()
    {
        $startDate = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($this->year, $this->month, 1)->endOfMonth();

        $this->appointments = Appointment::forUser(auth()->id())
            ->whereBetween('start_datetime', [$startDate, $endDate])
            ->with('appointmentType')
            ->orderBy('start_datetime')
            ->get()
            ->groupBy(function ($appointment) {
                return $appointment->start_datetime->format('Y-m-d');
            })
            ->toArray();
    }

    public function getCalendarDays()
    {
        $startOfMonth = Carbon::createFromDate($this->year, $this->month, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Start from Sunday of the week containing the first day
        $startDate = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);

        // End on Saturday of the week containing the last day
        $endDate = $endOfMonth->copy()->endOfWeek(Carbon::SATURDAY);

        $days = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $days[] = [
                'date' => $currentDate->copy(),
                'dateString' => $dateString,
                'day' => $currentDate->day,
                'isCurrentMonth' => $currentDate->month === $this->month,
                'isToday' => $currentDate->isToday(),
                'isPast' => $currentDate->isPast() && !$currentDate->isToday(),
                'appointments' => $this->appointments[$dateString] ?? [],
            ];

            $currentDate->addDay();
        }

        return $days;
    }

    public function render()
    {
        return view('livewire.calendar.appointment-calendar', [
            'calendarDays' => $this->getCalendarDays(),
            'monthName' => Carbon::createFromDate($this->year, $this->month, 1)->format('F Y'),
        ]);
    }
}
