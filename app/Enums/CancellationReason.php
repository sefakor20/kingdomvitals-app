<?php

declare(strict_types=1);

namespace App\Enums;

enum CancellationReason: string
{
    case TooExpensive = 'too_expensive';
    case MissingFeatures = 'missing_features';
    case NotUsing = 'not_using';
    case SwitchingProvider = 'switching_provider';
    case TechnicalIssues = 'technical_issues';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::TooExpensive => 'Too expensive',
            self::MissingFeatures => 'Missing features I need',
            self::NotUsing => 'Not using it enough',
            self::SwitchingProvider => 'Switching to another provider',
            self::TechnicalIssues => 'Technical issues',
            self::Other => 'Other',
        };
    }
}
