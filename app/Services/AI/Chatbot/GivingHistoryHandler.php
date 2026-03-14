<?php

declare(strict_types=1);

namespace App\Services\AI\Chatbot;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Member;

class GivingHistoryHandler
{
    /**
     * Handle giving history request.
     *
     * @param  array<string, mixed>  $entities
     */
    public function handle(?Member $member, Branch $branch, array $entities = []): string
    {
        if (! $member) {
            return "I couldn't find your member profile. Please contact the church office for your giving history.";
        }

        // Get donation summary
        $thisYear = now()->year;
        $yearDonations = $member->donations()
            ->whereYear('donation_date', $thisYear)
            ->get();

        $totalThisYear = $yearDonations->sum('amount');
        $donationCount = $yearDonations->count();

        // Get last donation
        $lastDonation = $member->donations()
            ->latest('donation_date')
            ->first();

        if ($donationCount === 0) {
            return "Hi {$member->first_name}! I don't see any donations recorded for you in {$thisYear} yet.";
        }

        $response = "Hi {$member->first_name}! Your {$thisYear} giving summary:\n";
        $response .= 'Total: '.number_format((float) $totalThisYear, 2)."\n";
        $response .= "Donations: {$donationCount}\n";

        if ($lastDonation) {
            $response .= 'Last gift: '.number_format((float) $lastDonation->amount, 2);
            $response .= ' on '.$lastDonation->donation_date->format('M j');
        }

        return $response;
    }
}
