<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EmailStatus;
use App\Enums\EmailType;
use App\Mail\BulkEmailMailable;
use App\Models\Tenant\Branch;
use App\Models\Tenant\EmailLog;
use App\Models\Tenant\Member;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BulkEmailService
{
    public function __construct(
        protected ?Branch $branch = null
    ) {}

    public static function forBranch(Branch $branch): self
    {
        return new self($branch);
    }

    /**
     * Send a single email and update the log.
     */
    public function send(EmailLog $emailLog): bool
    {
        try {
            Mail::to($emailLog->email_address)
                ->send(new BulkEmailMailable(
                    emailSubject: $emailLog->subject,
                    emailBody: $emailLog->body,
                    emailLog: $emailLog,
                    branch: $this->branch
                ));

            $emailLog->update([
                'status' => EmailStatus::Sent,
                'sent_at' => now(),
                'provider' => config('mail.default'),
            ]);

            return true;
        } catch (\Exception $e) {
            $emailLog->update([
                'status' => EmailStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create email log entries for multiple recipients.
     *
     * @param  Collection<int, Member>|array  $recipients
     * @return Collection<int, EmailLog>
     */
    public function createLogs(
        Collection|array $recipients,
        string $subject,
        string $body,
        EmailType $type,
        ?string $sentBy = null
    ): Collection {
        $recipients = collect($recipients);

        return $recipients->map(function ($recipient) use ($subject, $body, $type, $sentBy) {
            $email = $recipient instanceof Member ? $recipient->email : $recipient['email'];
            $memberId = $recipient instanceof Member ? $recipient->id : ($recipient['member_id'] ?? null);

            if (empty($email)) {
                return null;
            }

            $personalizedSubject = $this->personalizeContent($subject, $recipient);
            $personalizedBody = $this->personalizeContent($body, $recipient);

            // Convert Markdown to HTML for email sending
            $htmlBody = self::markdownToHtml($personalizedBody);

            return EmailLog::create([
                'branch_id' => $this->branch?->id,
                'member_id' => $memberId,
                'email_address' => $email,
                'subject' => $personalizedSubject,
                'body' => $htmlBody,
                'message_type' => $type,
                'status' => EmailStatus::Pending,
                'sent_by' => $sentBy,
            ]);
        })->filter();
    }

    /**
     * Personalize content with member placeholders.
     *
     * @param  Member|array  $recipient
     */
    public function personalizeContent(string $content, $recipient): string
    {
        $replacements = $this->getPlaceholderReplacements($recipient);

        foreach ($replacements as $placeholder => $value) {
            $content = str_replace($placeholder, $value ?? '', $content);
        }

        return $content;
    }

    /**
     * Get placeholder replacements for a recipient.
     *
     * @param  Member|array  $recipient
     * @return array<string, string|null>
     */
    protected function getPlaceholderReplacements($recipient): array
    {
        if ($recipient instanceof Member) {
            return [
                '{first_name}' => $recipient->first_name,
                '{last_name}' => $recipient->last_name,
                '{full_name}' => $recipient->full_name,
                '{email}' => $recipient->email,
                '{phone}' => $recipient->phone,
                '{branch_name}' => $this->branch?->name ?? '',
                '{month}' => now()->format('F'),
                '{year}' => now()->format('Y'),
            ];
        }

        return [
            '{first_name}' => $recipient['first_name'] ?? '',
            '{last_name}' => $recipient['last_name'] ?? '',
            '{full_name}' => trim(($recipient['first_name'] ?? '').' '.($recipient['last_name'] ?? '')),
            '{email}' => $recipient['email'] ?? '',
            '{phone}' => $recipient['phone'] ?? '',
            '{branch_name}' => $this->branch?->name ?? '',
            '{month}' => now()->format('F'),
            '{year}' => now()->format('Y'),
        ];
    }

    /**
     * Check if content contains placeholders that need personalization.
     */
    public function hasPlaceholders(string $content): bool
    {
        return (bool) preg_match('/\{(first_name|last_name|full_name|email|phone)\}/', $content);
    }

    /**
     * Get available placeholders with descriptions.
     *
     * @return array<string, string>
     */
    public static function getAvailablePlaceholders(): array
    {
        return [
            '{first_name}' => 'Member\'s first name',
            '{last_name}' => 'Member\'s last name',
            '{full_name}' => 'Member\'s full name',
            '{email}' => 'Member\'s email address',
            '{phone}' => 'Member\'s phone number',
            '{branch_name}' => 'Branch name',
            '{month}' => 'Current month name',
            '{year}' => 'Current year',
        ];
    }

    /**
     * Convert Markdown content to HTML.
     */
    public static function markdownToHtml(string $markdown): string
    {
        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * Get Markdown formatting guide.
     *
     * @return array<string, string>
     */
    public static function getMarkdownGuide(): array
    {
        return [
            '**bold**' => 'Bold text',
            '*italic*' => 'Italic text',
            '# Heading' => 'Large heading',
            '## Subheading' => 'Smaller heading',
            '- Item' => 'Bullet list',
            '1. Item' => 'Numbered list',
            '[text](url)' => 'Link',
        ];
    }
}
