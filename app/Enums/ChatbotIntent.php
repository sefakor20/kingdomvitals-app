<?php

declare(strict_types=1);

namespace App\Enums;

enum ChatbotIntent: string
{
    case GivingHistory = 'giving_history';
    case UpcomingEvents = 'upcoming_events';
    case PrayerRequest = 'prayer_request';
    case ClusterInfo = 'cluster_info';
    case Help = 'help';
    case Greeting = 'greeting';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::GivingHistory => 'Giving History',
            self::UpcomingEvents => 'Upcoming Events',
            self::PrayerRequest => 'Prayer Request',
            self::ClusterInfo => 'Cluster Information',
            self::Help => 'Help',
            self::Greeting => 'Greeting',
            self::Unknown => 'Unknown',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::GivingHistory => 'Query about donation history or giving records',
            self::UpcomingEvents => 'Query about church events or services',
            self::PrayerRequest => 'Submitting or asking about prayer requests',
            self::ClusterInfo => 'Query about small group or cluster information',
            self::Help => 'Request for help or list of capabilities',
            self::Greeting => 'Simple greeting or introduction',
            self::Unknown => 'Intent could not be determined',
        };
    }

    /**
     * Get sample phrases that trigger this intent.
     *
     * @return array<string>
     */
    public function samplePhrases(): array
    {
        return match ($this) {
            self::GivingHistory => [
                'how much have I given',
                'my donations',
                'giving history',
                'total tithe',
                'contribution statement',
            ],
            self::UpcomingEvents => [
                'upcoming events',
                'church events',
                'when is service',
                'next meeting',
                'what events',
            ],
            self::PrayerRequest => [
                'pray for',
                'prayer request',
                'need prayer',
                'submit prayer',
                'prayer',
            ],
            self::ClusterInfo => [
                'my cluster',
                'small group',
                'cluster meeting',
                'fellowship group',
                'cell group',
            ],
            self::Help => [
                'help',
                'what can you do',
                'commands',
                'options',
                'how to use',
            ],
            self::Greeting => [
                'hello',
                'hi',
                'good morning',
                'hey',
            ],
            self::Unknown => [],
        };
    }
}
