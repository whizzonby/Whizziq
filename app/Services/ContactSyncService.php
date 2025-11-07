<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;

class ContactSyncService
{
    /**
     * Sync appointment attendee to contacts automatically
     * Creates new contact if not exists, updates if exists, links to appointment
     *
     * @param Appointment $appointment
     * @return Contact|null
     */
    public function syncAppointmentToContact(Appointment $appointment): ?Contact
    {
        // Skip if appointment already has a contact linked
        if ($appointment->contact_id) {
            return $appointment->contact;
        }

        // Skip if no attendee email (email is our primary matching field)
        if (empty($appointment->attendee_email)) {
            Log::info('Appointment sync skipped - no attendee email', [
                'appointment_id' => $appointment->id
            ]);
            return null;
        }

        try {
            // Find or create contact
            $contact = $this->findOrCreateContact($appointment);

            // Link appointment to contact
            $appointment->update(['contact_id' => $contact->id]);

            // Log the interaction
            $this->logAppointmentInteraction($contact, $appointment);

            Log::info('Appointment synced to contact', [
                'appointment_id' => $appointment->id,
                'contact_id' => $contact->id,
                'contact_email' => $contact->email,
            ]);

            return $contact;

        } catch (\Exception $e) {
            Log::error('Failed to sync appointment to contact', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find existing contact or create new one from appointment data
     *
     * @param Appointment $appointment
     * @return Contact
     */
    protected function findOrCreateContact(Appointment $appointment): Contact
    {
        // Try to find existing contact by email
        $contact = Contact::where('user_id', $appointment->user_id)
            ->where('email', $appointment->attendee_email)
            ->first();

        if ($contact) {
            // Update contact with latest appointment info if empty
            $this->updateContactFromAppointment($contact, $appointment);
            return $contact;
        }

        // Create new contact from appointment data
        return $this->createContactFromAppointment($appointment);
    }

    /**
     * Create new contact from appointment data
     *
     * @param Appointment $appointment
     * @return Contact
     */
    protected function createContactFromAppointment(Appointment $appointment): Contact
    {
        $nameParts = $this->parseFullName($appointment->attendee_name ?? 'Unknown');

        return Contact::create([
            'user_id' => $appointment->user_id,
            'name' => $appointment->attendee_name ?? 'Unknown',
            'email' => $appointment->attendee_email,
            'phone' => $appointment->attendee_phone,
            'company' => $appointment->attendee_company,
            'type' => 'lead', // New appointments start as leads
            'status' => 'active',
            'priority' => 'medium',
            'source' => 'appointment_booking',
            'last_contact_date' => now(),
            'notes' => "Contact created from appointment: {$appointment->title}",
            'relationship_strength' => 'warm',
        ]);
    }

    /**
     * Update existing contact with appointment data (only fill empty fields)
     *
     * @param Contact $contact
     * @param Appointment $appointment
     * @return void
     */
    protected function updateContactFromAppointment(Contact $contact, Appointment $appointment): void
    {
        $updates = [];

        // Only update if fields are empty
        if (empty($contact->phone) && !empty($appointment->attendee_phone)) {
            $updates['phone'] = $appointment->attendee_phone;
        }

        if (empty($contact->company) && !empty($appointment->attendee_company)) {
            $updates['company'] = $appointment->attendee_company;
        }

        // Always update last contact date
        $updates['last_contact_date'] = now();

        if (!empty($updates)) {
            $contact->update($updates);
        }
    }

    /**
     * Log appointment as a contact interaction
     *
     * @param Contact $contact
     * @param Appointment $appointment
     * @return void
     */
    protected function logAppointmentInteraction(Contact $contact, Appointment $appointment): void
    {
        $description = "Appointment booked: {$appointment->title}";
        $description .= "\nScheduled for: {$appointment->start_datetime->format('M d, Y \a\t g:i A')}";

        if ($appointment->location) {
            $description .= "\nLocation: {$appointment->location}";
        }

        if ($appointment->meeting_url) {
            $description .= "\nMeeting Link: {$appointment->meeting_url}";
        }

        $contact->logInteraction(
            type: 'appointment',
            description: $description,
            interactionDate: $appointment->created_at,
            subject: $appointment->title,
            durationMinutes: $appointment->duration_minutes
        );
    }

    /**
     * Parse full name into first and last name
     *
     * @param string $fullName
     * @return array
     */
    protected function parseFullName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName), 2);

        return [
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
        ];
    }

    /**
     * Sync all appointments without contacts
     * Useful for initial migration or batch processing
     *
     * @param int|null $userId
     * @return array
     */
    public function syncAllUnlinkedAppointments(?int $userId = null): array
    {
        $query = Appointment::whereNull('contact_id')
            ->whereNotNull('attendee_email');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $appointments = $query->get();
        $synced = 0;
        $failed = 0;

        foreach ($appointments as $appointment) {
            $contact = $this->syncAppointmentToContact($appointment);

            if ($contact) {
                $synced++;
            } else {
                $failed++;
            }
        }

        return [
            'total' => $appointments->count(),
            'synced' => $synced,
            'failed' => $failed,
        ];
    }

    /**
     * Get contact variables for email templates
     *
     * @param Contact $contact
     * @param \App\Models\User $owner
     * @return array
     */
    public function getContactVariables(Contact $contact, \App\Models\User $owner): array
    {
        $nameParts = $this->parseFullName($contact->name);

        // Get next appointment
        $nextAppointment = $contact->appointments()
            ->where('start_datetime', '>=', now())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderBy('start_datetime')
            ->first();

        return [
            // Basic Contact Info
            'name' => $contact->name,
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'],
            'email' => $contact->email,
            'phone' => $contact->phone ?? '',
            'company' => $contact->company ?? '',
            'job_title' => $contact->job_title ?? '',

            // Advanced Contact Info
            'address' => $contact->full_address ?? '',
            'city' => $contact->city ?? '',
            'state' => $contact->state ?? '',
            'country' => $contact->country ?? '',
            'website' => $contact->website ?? '',

            // Appointment Info
            'next_appointment_date' => $nextAppointment ? $nextAppointment->start_datetime->format('F j, Y') : 'N/A',
            'next_appointment_time' => $nextAppointment ? $nextAppointment->start_datetime->format('g:i A') : 'N/A',
            'appointment_type' => $nextAppointment ? $nextAppointment->appointmentType?->name : 'N/A',

            // Relationship Info
            'last_contact_date' => $contact->last_contact_date?->format('F j, Y') ?? 'N/A',
            'days_since_last_contact' => $contact->days_since_last_contact ?? 'N/A',
            'relationship_strength' => ucfirst($contact->relationship_strength),
            'lifetime_value' => number_format($contact->lifetime_value, 2),

            // Business Owner Info
            'owner_name' => $owner->name,
            'owner_email' => $owner->email,
            'owner_phone' => $owner->phone_number ?? '',
            'owner_company' => $owner->businessProfile?->biz_name ?? '',

            // System
            'current_date' => now()->format('F j, Y'),
            'current_year' => now()->format('Y'),
        ];
    }
}
