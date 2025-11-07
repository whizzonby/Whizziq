<?php

namespace App\Console\Commands;

use App\Models\EmailLog;
use App\Services\EmailCampaignService;
use App\Services\ContactSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendScheduledEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:send-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled emails that are due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for scheduled emails...');

        // Get all scheduled emails that are due
        $scheduledEmails = EmailLog::pendingSend()->get();

        if ($scheduledEmails->isEmpty()) {
            $this->info('No scheduled emails to send.');
            return Command::SUCCESS;
        }

        $this->info("Found {$scheduledEmails->count()} scheduled email(s) to send.");

        $sent = 0;
        $failed = 0;

        foreach ($scheduledEmails as $emailLog) {
            try {
                $this->sendScheduledEmail($emailLog);
                $sent++;
                $this->info("✓ Sent email to {$emailLog->recipient_email}");
            } catch (\Exception $e) {
                $failed++;
                $emailLog->markAsFailed($e->getMessage());
                $this->error("✗ Failed to send email to {$emailLog->recipient_email}: {$e->getMessage()}");

                Log::error('Failed to send scheduled email', [
                    'email_log_id' => $emailLog->id,
                    'recipient' => $emailLog->recipient_email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("\nSummary: {$sent} sent, {$failed} failed");

        return Command::SUCCESS;
    }

    /**
     * Send a scheduled email
     */
    protected function sendScheduledEmail(EmailLog $emailLog)
    {
        $metadata = $emailLog->metadata ?? [];
        $fromEmail = $metadata['from_email'] ?? config('mail.from.address');
        $fromName = $metadata['from_name'] ?? config('mail.from.name');
        $replyTo = $metadata['reply_to'] ?? $fromEmail;

        // Get contact variables if contact is linked
        if ($emailLog->contact_id) {
            $contact = $emailLog->contact;
            $user = $emailLog->user;
            $contactSyncService = app(ContactSyncService::class);
            $variables = $contactSyncService->getContactVariables($contact, $user);

            // Replace variables in subject and body
            $subject = $this->replaceVariables($emailLog->subject, $variables);
            $body = $this->replaceVariables($emailLog->body, $variables);
        } else {
            $subject = $emailLog->subject;
            $body = $emailLog->body;
        }

        // Send the email
        \Mail::html($body, function ($message) use ($emailLog, $fromEmail, $fromName, $replyTo, $subject) {
            $message->to($emailLog->recipient_email, $emailLog->recipient_name)
                ->subject($subject)
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
        });

        // Mark as sent
        $emailLog->markAsSent();

        // Log interaction with contact if linked
        if ($emailLog->contact) {
            $emailLog->contact->logInteraction(
                type: 'email',
                description: "Scheduled email sent: {$subject}",
                subject: $subject
            );
        }
    }

    /**
     * Replace variables in content
     */
    protected function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value ?? '', $content);
        }

        return $content;
    }
}
