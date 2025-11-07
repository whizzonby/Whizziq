<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Collection;

class DuplicateContactDetectionService
{
    /**
     * Find potential duplicate contacts
     */
    public function findDuplicates(array $contactData, int $userId, ?int $excludeContactId = null): Collection
    {
        $duplicates = collect();

        // Check by exact email match
        if (!empty($contactData['email'])) {
            $emailMatches = Contact::where('user_id', $userId)
                ->where('email', $contactData['email'])
                ->when($excludeContactId, fn($q) => $q->where('id', '!=', $excludeContactId))
                ->get();

            foreach ($emailMatches as $match) {
                $duplicates->push([
                    'contact' => $match,
                    'match_type' => 'exact_email',
                    'confidence' => 100,
                    'reason' => 'Email address matches exactly',
                ]);
            }
        }

        // Check by exact phone match
        if (!empty($contactData['phone'])) {
            $cleanPhone = $this->cleanPhoneNumber($contactData['phone']);

            $phoneMatches = Contact::where('user_id', $userId)
                ->where('phone', 'like', '%' . substr($cleanPhone, -10) . '%')
                ->when($excludeContactId, fn($q) => $q->where('id', '!=', $excludeContactId))
                ->get()
                ->filter(function($contact) use ($duplicates) {
                    // Don't add if already found by email
                    return !$duplicates->contains('contact.id', $contact->id);
                });

            foreach ($phoneMatches as $match) {
                $duplicates->push([
                    'contact' => $match,
                    'match_type' => 'exact_phone',
                    'confidence' => 95,
                    'reason' => 'Phone number matches',
                ]);
            }
        }

        // Check by similar name + company
        if (!empty($contactData['name'])) {
            $similarMatches = Contact::where('user_id', $userId)
                ->when($excludeContactId, fn($q) => $q->where('id', '!=', $excludeContactId))
                ->get()
                ->filter(function($contact) use ($contactData, $duplicates) {
                    // Don't add if already found
                    if ($duplicates->contains('contact.id', $contact->id)) {
                        return false;
                    }

                    $nameSimilarity = $this->calculateSimilarity(
                        strtolower($contactData['name']),
                        strtolower($contact->name)
                    );

                    // If names are very similar
                    if ($nameSimilarity >= 85) {
                        // And company matches (if provided)
                        if (!empty($contactData['company']) && !empty($contact->company)) {
                            $companySimilarity = $this->calculateSimilarity(
                                strtolower($contactData['company']),
                                strtolower($contact->company)
                            );

                            return $companySimilarity >= 80;
                        }

                        // Or just high name similarity
                        return $nameSimilarity >= 90;
                    }

                    return false;
                });

            foreach ($similarMatches as $match) {
                $confidence = $this->calculateSimilarity(
                    strtolower($contactData['name']),
                    strtolower($match->name)
                );

                $duplicates->push([
                    'contact' => $match,
                    'match_type' => 'similar_name',
                    'confidence' => (int)$confidence,
                    'reason' => 'Name is very similar',
                ]);
            }
        }

        return $duplicates->sortByDesc('confidence')->values();
    }

    /**
     * Calculate similarity between two strings (0-100)
     */
    protected function calculateSimilarity(string $str1, string $str2): float
    {
        similar_text($str1, $str2, $percent);
        return $percent;
    }

    /**
     * Clean phone number for comparison
     */
    protected function cleanPhoneNumber(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Get duplicate suggestions for display
     */
    public function getSuggestions(array $contactData, int $userId, ?int $excludeContactId = null): array
    {
        $duplicates = $this->findDuplicates($contactData, $userId, $excludeContactId);

        if ($duplicates->isEmpty()) {
            return [
                'has_duplicates' => false,
                'suggestions' => [],
            ];
        }

        return [
            'has_duplicates' => true,
            'count' => $duplicates->count(),
            'suggestions' => $duplicates->take(5)->map(function($duplicate) {
                return [
                    'id' => $duplicate['contact']->id,
                    'name' => $duplicate['contact']->name,
                    'email' => $duplicate['contact']->email,
                    'phone' => $duplicate['contact']->phone,
                    'company' => $duplicate['contact']->company,
                    'match_type' => $duplicate['match_type'],
                    'confidence' => $duplicate['confidence'],
                    'reason' => $duplicate['reason'],
                ];
            })->toArray(),
        ];
    }

    /**
     * Merge two contacts
     */
    public function mergeContacts(Contact $primaryContact, Contact $duplicateContact): Contact
    {
        // Merge data (keep primary, fill in blanks from duplicate)
        $primaryContact->update([
            'email' => $primaryContact->email ?: $duplicateContact->email,
            'phone' => $primaryContact->phone ?: $duplicateContact->phone,
            'company' => $primaryContact->company ?: $duplicateContact->company,
            'job_title' => $primaryContact->job_title ?: $duplicateContact->job_title,
            'address' => $primaryContact->address ?: $duplicateContact->address,
            'city' => $primaryContact->city ?: $duplicateContact->city,
            'state' => $primaryContact->state ?: $duplicateContact->state,
            'zip' => $primaryContact->zip ?: $duplicateContact->zip,
            'country' => $primaryContact->country ?: $duplicateContact->country,
            'website' => $primaryContact->website ?: $duplicateContact->website,
            'linkedin_url' => $primaryContact->linkedin_url ?: $duplicateContact->linkedin_url,
            'twitter_handle' => $primaryContact->twitter_handle ?: $duplicateContact->twitter_handle,
            'notes' => trim(($primaryContact->notes ?? '') . "\n\n" . ($duplicateContact->notes ?? '')),
        ]);

        // Transfer relationships
        $duplicateContact->deals()->update(['contact_id' => $primaryContact->id]);
        $duplicateContact->interactions()->update(['contact_id' => $primaryContact->id]);
        $duplicateContact->reminders()->update(['contact_id' => $primaryContact->id]);
        $duplicateContact->appointments()->update(['contact_id' => $primaryContact->id]);
        $duplicateContact->emailLogs()->update(['contact_id' => $primaryContact->id]);

        // Update metrics
        $primaryContact->deals_count += $duplicateContact->deals_count;
        $primaryContact->interactions_count += $duplicateContact->interactions_count;
        $primaryContact->lifetime_value += $duplicateContact->lifetime_value;

        // Keep the earlier last contact date
        if ($duplicateContact->last_contact_date &&
            (!$primaryContact->last_contact_date ||
             $duplicateContact->last_contact_date->lt($primaryContact->last_contact_date))) {
            $primaryContact->last_contact_date = $duplicateContact->last_contact_date;
        }

        $primaryContact->save();

        // Soft delete the duplicate
        $duplicateContact->delete();

        return $primaryContact->fresh();
    }

    /**
     * Find all duplicates in the database
     */
    public function findAllDuplicates(int $userId): Collection
    {
        $contacts = Contact::where('user_id', $userId)->get();
        $duplicateGroups = collect();

        foreach ($contacts as $contact) {
            $duplicates = $this->findDuplicates([
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'company' => $contact->company,
            ], $userId, $contact->id);

            if ($duplicates->isNotEmpty()) {
                $groupKey = collect([$contact->id, ...$duplicates->pluck('contact.id')])
                    ->sort()
                    ->implode('-');

                if (!$duplicateGroups->has($groupKey)) {
                    $duplicateGroups->put($groupKey, [
                        'primary' => $contact,
                        'duplicates' => $duplicates,
                    ]);
                }
            }
        }

        return $duplicateGroups->values();
    }
}
