<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
        <div id="calendar"></div>
    </div>

    {{-- Event Details Modal (rendered by FullCalendar) --}}
    <div id="eventModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title"></h3>
                <div class="mt-4 space-y-2 text-sm text-gray-700 dark:text-gray-300" id="modal-content">
                </div>
                <div class="flex gap-2 mt-6">
                    <button
                        onclick="viewAppointmentDetails()"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                        View Details
                    </button>
                    <button
                        onclick="closeEventModal()"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-white text-sm font-medium rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

        <script>
            let calendar;
            let selectedEventId = null;

            document.addEventListener('DOMContentLoaded', function() {
                var calendarEl = document.getElementById('calendar');

                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                    },
                    buttonText: {
                        today: 'Today',
                        month: 'Month',
                        week: 'Week',
                        day: 'Day',
                        list: 'List'
                    },
                    height: 'auto',
                    navLinks: true,
                    selectable: true,
                    selectMirror: true,
                    editable: false, // Can enable for drag-and-drop rescheduling
                    dayMaxEvents: true,
                    events: {!! json_encode($this->getAppointmentsForCalendar()) !!},
                    eventClick: function(info) {
                        showEventDetails(info.event);
                    },
                    select: function(info) {
                        // Could add "Create Appointment" functionality here
                        console.log('Selected date range:', info.start, info.end);
                    },
                    eventDidMount: function(info) {
                        // Add tooltip
                        info.el.title = info.event.title + '\n' +
                            'Attendee: ' + (info.event.extendedProps.attendee || 'N/A') + '\n' +
                            'Status: ' + info.event.extendedProps.status;
                    }
                });

                calendar.render();
            });

            function showEventDetails(event) {
                selectedEventId = event.id;
                const props = event.extendedProps;

                document.getElementById('modal-title').textContent = event.title;

                const statusColors = {
                    'confirmed': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    'scheduled': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                    'completed': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                    'cancelled': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                    'no_show': 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
                };

                const statusColor = statusColors[props.status] || statusColors['no_show'];

                let content = `
                    <div class="space-y-2">
                        <div><strong>Time:</strong> ${formatDateTime(event.start)} - ${formatTime(event.end)}</div>
                        <div><strong>Type:</strong> ${props.type}</div>
                        <div>
                            <strong>Status:</strong>
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium ${statusColor}">
                                ${capitalizeFirst(props.status)}
                            </span>
                        </div>
                        <div><strong>Attendee:</strong> ${props.attendee || 'N/A'}</div>
                        ${props.email ? `<div><strong>Email:</strong> ${props.email}</div>` : ''}
                        ${props.phone ? `<div><strong>Phone:</strong> ${props.phone}</div>` : ''}
                        ${props.location ? `<div><strong>Location:</strong> ${props.location}</div>` : ''}
                        ${props.meeting_url ? `<div><strong>Meeting:</strong> <a href="${props.meeting_url}" target="_blank" class="text-blue-600 hover:text-blue-800">Join Meeting</a></div>` : ''}
                    </div>
                `;

                document.getElementById('modal-content').innerHTML = content;
                document.getElementById('eventModal').classList.remove('hidden');
            }

            function closeEventModal() {
                document.getElementById('eventModal').classList.add('hidden');
                selectedEventId = null;
            }

            function viewAppointmentDetails() {
                if (selectedEventId) {
                    window.location.href = `/dashboard/appointments/${selectedEventId}/edit`;
                }
            }

            function formatDateTime(date) {
                return new Date(date).toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            }

            function formatTime(date) {
                return new Date(date).toLocaleString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            }

            function capitalizeFirst(string) {
                return string.charAt(0).toUpperCase() + string.slice(1).replace('_', ' ');
            }

            // Close modal when clicking outside
            document.getElementById('eventModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeEventModal();
                }
            });
        </script>
    @endpush
</x-filament-panels::page>
