<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Visitor;

class FollowUpTemplatePlaceholderService
{
    /**
     * Get available placeholders with their descriptions.
     *
     * @return array<string, string>
     */
    public function getAvailablePlaceholders(): array
    {
        return [
            '{first_name}' => "Visitor's first name",
            '{last_name}' => "Visitor's last name",
            '{full_name}' => "Visitor's full name",
            '{visit_date}' => 'First visit date',
            '{branch_name}' => 'Branch name',
            '{days_since_visit}' => 'Days since first visit',
            '{phone}' => 'Phone number',
            '{email}' => 'Email address',
        ];
    }

    /**
     * Replace placeholders in a template with actual visitor data.
     */
    public function replacePlaceholders(string $template, Visitor $visitor, Branch $branch): string
    {
        $replacements = [
            '{first_name}' => $visitor->first_name ?? '',
            '{last_name}' => $visitor->last_name ?? '',
            '{full_name}' => $visitor->fullName(),
            '{visit_date}' => $visitor->visit_date?->format('M d, Y') ?? '',
            '{branch_name}' => $branch->name,
            '{days_since_visit}' => $visitor->visit_date ? (string) (int) $visitor->visit_date->diffInDays(now()) : '',
            '{phone}' => $visitor->phone ?? '',
            '{email}' => $visitor->email ?? '',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }
}
