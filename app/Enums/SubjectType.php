<?php

namespace App\Enums;

enum SubjectType: string
{
    case Member = 'member';
    case Donation = 'donation';
    case Visitor = 'visitor';
    case Expense = 'expense';
    case Pledge = 'pledge';
    case Equipment = 'equipment';
    case Cluster = 'cluster';
    case Service = 'service';
    case User = 'user';
    case Event = 'event';
    case EventRegistration = 'event_registration';

    public function label(): string
    {
        return match ($this) {
            self::Member => 'Member',
            self::Donation => 'Donation',
            self::Visitor => 'Visitor',
            self::Expense => 'Expense',
            self::Pledge => 'Pledge',
            self::Equipment => 'Equipment',
            self::Cluster => 'Cluster',
            self::Service => 'Service',
            self::User => 'User',
            self::Event => 'Event',
            self::EventRegistration => 'Event Registration',
        };
    }

    public function pluralLabel(): string
    {
        return match ($this) {
            self::Member => 'Members',
            self::Donation => 'Donations',
            self::Visitor => 'Visitors',
            self::Expense => 'Expenses',
            self::Pledge => 'Pledges',
            self::Equipment => 'Equipment',
            self::Cluster => 'Clusters',
            self::Service => 'Services',
            self::User => 'Users',
            self::Event => 'Events',
            self::EventRegistration => 'Event Registrations',
        };
    }

    public function modelClass(): string
    {
        return match ($this) {
            self::Member => \App\Models\Tenant\Member::class,
            self::Donation => \App\Models\Tenant\Donation::class,
            self::Visitor => \App\Models\Tenant\Visitor::class,
            self::Expense => \App\Models\Tenant\Expense::class,
            self::Pledge => \App\Models\Tenant\Pledge::class,
            self::Equipment => \App\Models\Tenant\Equipment::class,
            self::Cluster => \App\Models\Tenant\Cluster::class,
            self::Service => \App\Models\Tenant\Service::class,
            self::User => \App\Models\User::class,
            self::Event => \App\Models\Tenant\Event::class,
            self::EventRegistration => \App\Models\Tenant\EventRegistration::class,
        };
    }
}
