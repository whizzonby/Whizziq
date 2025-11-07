<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\EmailCampaign;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailCampaignService
{
    public function __construct(
        protected ContactSyncService $contactSyncService
    ) {}

    /**
     * Send email to a single contact
     *
     * @param User $user
     * @param Contact $contact
     * @param string $subject
     * @param string $body
     * @param array $options
     * @return EmailLog
     */
    public function sendToContact(
        User $user,
        Contact $contact,
        string $subject,
        string $body,
        array $options = []
    ): EmailLog {
        // Validate contact has email
        if (empty($contact->email)) {
            Log::error('Failed to send email from compose page', [
                'contact_id' => $contact->id,
                'contact_email' => $contact->email,
                'error' => 'Contact does not have an email address',
            ]);

            throw new \InvalidArgumentException("Contact '{$contact->name}' does not have an email address.");
        }

        // Get contact variables
        $variables = $this->contactSyncService->getContactVariables($contact, $user);

        // Replace variables in subject and body
        $processedSubject = $this->replaceVariables($subject, $variables);
        $processedBody = $this->replaceVariables($body, $variables);

        // Create email log
        $emailLog = EmailLog::create([
            'user_id' => $user->id,
            'contact_id' => $contact->id,
            'recipient_email' => $contact->email,
            'recipient_name' => $contact->name,
            'subject' => $processedSubject,
            'body' => $processedBody,
            'status' => 'pending',
            'email_type' => $options['email_type'] ?? 'manual',
            'metadata' => $options['metadata'] ?? null,
            'attachments' => $options['attachments'] ?? null,
        ]);

        // Send email
        try {
            $this->sendEmail($emailLog, $user, $options);

            // Log as contact interaction
            if ($emailLog->status === 'sent') {
                $contact->logInteraction(
                    type: 'email',
                    description: "Email sent: {$processedSubject}",
                    subject: $processedSubject
                );
            }

        } catch (\Exception $e) {
            Log::error('Failed to send email to contact', [
                'contact_id' => $contact->id,
                'email' => $contact->email,
                'error' => $e->getMessage(),
            ]);

            $emailLog->markAsFailed($e->getMessage());
        }

        return $emailLog;
    }

    /**
     * Send email using template
     *
     * @param User $user
     * @param Contact $contact
     * @param EmailTemplate $template
     * @param array $options
     * @return EmailLog
     */
    public function sendWithTemplate(
        User $user,
        Contact $contact,
        EmailTemplate $template,
        array $options = []
    ): EmailLog {
        // Increment template usage
        $template->incrementUsage();

        return $this->sendToContact(
            user: $user,
            contact: $contact,
            subject: $template->subject,
            body: $template->body,
            options: array_merge($options, [
                'email_template_id' => $template->id,
            ])
        );
    }

    /**
     * Send campaign to multiple contacts
     *
     * @param EmailCampaign $campaign
     * @return array
     */
    public function sendCampaign(EmailCampaign $campaign): array
    {
        if ($campaign->status !== 'draft' && $campaign->status !== 'scheduled') {
            throw new \Exception('Campaign must be in draft or scheduled status to send');
        }

        // Mark campaign as sending
        $campaign->markAsSending();

        // Get recipients
        $contacts = $this->getCampaignRecipients($campaign);

        $campaign->update(['total_recipients' => $contacts->count()]);

        $sent = 0;
        $failed = 0;

        foreach ($contacts as $contact) {
            try {
                $emailLog = $this->sendToContact(
                    user: $campaign->user,
                    contact: $contact,
                    subject: $campaign->subject,
                    body: $campaign->body,
                    options: [
                        'email_type' => 'campaign',
                        'email_campaign_id' => $campaign->id,
                        'from_name' => $campaign->from_name,
                        'from_email' => $campaign->from_email,
                        'reply_to' => $campaign->reply_to,
                    ]
                );

                if ($emailLog->status === 'sent') {
                    $sent++;
                    $campaign->incrementSent();
                } else {
                    $failed++;
                    $campaign->incrementFailed();
                }

            } catch (\Exception $e) {
                $failed++;
                $campaign->incrementFailed();

                Log::error('Campaign email failed', [
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Mark campaign as sent
        $campaign->markAsSent();

        return [
            'total_recipients' => $contacts->count(),
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    /**
     * Get campaign recipients based on filters
     *
     * @param EmailCampaign $campaign
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getCampaignRecipients(EmailCampaign $campaign)
    {
        $query = Contact::where('user_id', $campaign->user_id)
            ->where('status', 'active');

        switch ($campaign->recipient_type) {
            case 'all_contacts':
                // All active contacts
                break;

            case 'individual':
            case 'custom_list':
                // Specific contact IDs
                if ($campaign->recipient_ids) {
                    $query->whereIn('id', $campaign->recipient_ids);
                }
                break;

            case 'filtered':
                // Apply filters
                if ($campaign->recipient_filters) {
                    $this->applyContactFilters($query, $campaign->recipient_filters);
                }
                break;
        }

        return $query->get();
    }

    /**
     * Apply filters to contact query
     *
     * @param $query
     * @param array $filters
     * @return void
     */
    protected function applyContactFilters($query, array $filters): void
    {
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['relationship_strength'])) {
            $query->where('relationship_strength', $filters['relationship_strength']);
        }

        if (isset($filters['tags']) && !empty($filters['tags'])) {
            foreach ($filters['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }
    }

    /**
     * Send actual email via Laravel Mail
     *
     * @param EmailLog $emailLog
     * @param User $user
     * @param array $options
     * @return void
     */
    protected function sendEmail(EmailLog $emailLog, User $user, array $options = []): void
    {
        $fromEmail = $options['from_email'] ?? config('mail.from.address');
        $fromName = $options['from_name'] ?? config('mail.from.name');
        $replyTo = $options['reply_to'] ?? $fromEmail;

        try {
            Mail::html($emailLog->body, function ($message) use ($emailLog, $fromEmail, $fromName, $replyTo) {
                $message->to($emailLog->recipient_email, $emailLog->recipient_name)
                    ->subject($emailLog->subject)
                    ->from($fromEmail, $fromName)
                    ->replyTo($replyTo);

                // Attach files if present
                if ($emailLog->attachments && is_array($emailLog->attachments)) {
                    foreach ($emailLog->attachments as $attachment) {
                        if (file_exists(storage_path('app/' . $attachment))) {
                            $message->attach(storage_path('app/' . $attachment));
                        }
                    }
                }

                // Add tracking pixel for open tracking (for later webhook integration)
                // This will be handled by your email provider (Postmark, Mailgun, etc.)
            });

            // Mark as sent (with message ID if available)
            $emailLog->markAsSent();

            Log::info('Email sent successfully', [
                'email_log_id' => $emailLog->id,
                'recipient' => $emailLog->recipient_email,
            ]);

        } catch (\Exception $e) {
            $emailLog->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Replace variables in content
     *
     * @param string $content
     * @param array $variables
     * @return string
     */
    protected function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value ?? '', $content);
        }

        return $content;
    }

    /**
     * Create email campaign
     *
     * @param User $user
     * @param array $data
     * @return EmailCampaign
     */
    public function createCampaign(User $user, array $data): EmailCampaign
    {
        return EmailCampaign::create([
            'user_id' => $user->id,
            'email_template_id' => $data['email_template_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'recipient_type' => $data['recipient_type'] ?? 'individual',
            'recipient_filters' => $data['recipient_filters'] ?? null,
            'recipient_ids' => $data['recipient_ids'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'from_name' => $data['from_name'] ?? null,
            'from_email' => $data['from_email'] ?? null,
            'reply_to' => $data['reply_to'] ?? null,
        ]);
    }

    /**
     * Preview email with contact variables
     *
     * @param string $subject
     * @param string $body
     * @param Contact $contact
     * @param User $user
     * @return array
     */
    public function previewEmail(string $subject, string $body, Contact $contact, User $user): array
    {
        $variables = $this->contactSyncService->getContactVariables($contact, $user);

        return [
            'subject' => $this->replaceVariables($subject, $variables),
            'body' => $this->replaceVariables($body, $variables),
            'variables_used' => $variables,
        ];
    }

    /**
     * Get email statistics for a user
     *
     * @param User $user
     * @param int $days
     * @return array
     */
    public function getEmailStats(User $user, int $days = 30): array
    {
        $emailLogs = EmailLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        $sent = $emailLogs->where('status', 'sent')->count();
        $failed = $emailLogs->where('status', 'failed')->count();
        $opened = $emailLogs->whereNotNull('opened_at')->count();
        $clicked = $emailLogs->whereNotNull('clicked_at')->count();

        return [
            'total_sent' => $sent,
            'total_failed' => $failed,
            'total_opened' => $opened,
            'total_clicked' => $clicked,
            'open_rate' => $sent > 0 ? round(($opened / $sent) * 100, 2) : 0,
            'click_rate' => $sent > 0 ? round(($clicked / $sent) * 100, 2) : 0,
            'delivery_rate' => ($sent + $failed) > 0 ? round(($sent / ($sent + $failed)) * 100, 2) : 0,
        ];
    }
}
