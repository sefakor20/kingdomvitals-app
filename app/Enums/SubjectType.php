<?php

namespace App\Enums;

use App\Models\Tenant\Cluster;
use App\Models\Tenant\Donation;
use App\Models\Tenant\Equipment;
use App\Models\Tenant\Event;
use App\Models\Tenant\EventRegistration;
use App\Models\Tenant\Expense;
use App\Models\Tenant\Member;
use App\Models\Tenant\Pledge;
use App\Models\Tenant\Service;
use App\Models\Tenant\Visitor;
use App\Models\User;

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
            self::Member => Member::class,
            self::Donation => Donation::class,
            self::Visitor => Visitor::class,
            self::Expense => Expense::class,
            self::Pledge => Pledge::class,
            self::Equipment => Equipment::class,
            self::Cluster => Cluster::class,
            self::Service => Service::class,
            self::User => User::class,
            self::Event => Event::class,
            self::EventRegistration => EventRegistration::class,
        };
    }
}
