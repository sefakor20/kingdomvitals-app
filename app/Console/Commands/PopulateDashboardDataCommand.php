<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ClusterHealthLevel;
use App\Enums\HouseholdEngagementLevel;
use App\Enums\LifecycleStage;
use App\Enums\SmsEngagementLevel;
use App\Models\Domain;
use App\Models\Tenant;
use App\Models\Tenant\AttendanceForecast;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\FinancialForecast;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Models\Tenant\MemberActivity;
use App\Models\Tenant\Service;
use App\Models\Tenant\Visitor;
use App\Models\Tenant\VisitorFollowUp;
use Illuminate\Console\Command;

class PopulateDashboardDataCommand extends Command
{
    protected $signature = 'tenant:populate-dashboard
                            {tenant : The tenant ID (UUID) or domain name}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Populate AI fields and dashboard data for existing tenant records';

    public function handle(): int
    {
        $tenant = $this->resolveTenant($this->argument('tenant'));

        if (! $tenant) {
            $this->error('Tenant not found.');

            return Command::FAILURE;
        }

        $this->info("Populating dashboard data for tenant: {$tenant->name}");

        if (! $this->option('force') && ! $this->confirm('This will update existing records with AI fields. Continue?')) {
            $this->info('Aborted.');

            return Command::SUCCESS;
        }

        try {
            tenancy()->initialize($tenant);

            $this->populateMemberAiFields();
            $this->populateVisitorAiFields();
            $this->populateClusterHealthLevels();
            $this->populateHouseholdEngagementLevels();
            $this->createMemberActivities();
            $this->createVisitorFollowUps();
            $this->updateMemberNames();
            $this->updateClusterNames();

            // Create forecasts for the main branch
            $branch = Branch::where('is_main', true)->first();
            if ($branch) {
                $this->createFinancialForecasts($branch->id);
                $this->createAttendanceForecasts($branch->id);
            }

            tenancy()->end();

            $this->newLine();
            $this->info('Dashboard data populated successfully!');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            tenancy()->end();

            $this->error("Error: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    protected function resolveTenant(string $identifier): ?Tenant
    {
        $tenant = Tenant::find($identifier);

        if ($tenant) {
            return $tenant;
        }

        $domain = Domain::where('domain', $identifier)->first();

        return $domain?->tenant;
    }

    protected function populateMemberAiFields(): void
    {
        $members = Member::whereNull('lifecycle_stage')->get();
        $count = $members->count();

        if ($count === 0) {
            $this->line('No members need AI field updates.');

            return;
        }

        $this->line("Updating {$count} members with AI fields...");

        $members->each(function ($member) {
            $member->update([
                'churn_risk_score' => fake()->optional(0.3)->randomFloat(2, 0, 100),
                'lifecycle_stage' => fake()->randomElement(LifecycleStage::cases()),
                'sms_engagement_level' => fake()->randomElement(SmsEngagementLevel::cases()),
                'attendance_anomaly_detected_at' => fake()->optional(0.1)->dateTimeBetween('-7 days', 'now'),
            ]);
        });

        $this->info("  Updated {$count} members.");
    }

    protected function populateVisitorAiFields(): void
    {
        $visitors = Visitor::whereNull('conversion_score')->get();
        $count = $visitors->count();

        if ($count === 0) {
            $this->line('No visitors need AI field updates.');

            return;
        }

        $this->line("Updating {$count} visitors with conversion scores...");

        $visitors->each(function ($visitor) {
            $visitor->update([
                'conversion_score' => fake()->optional(0.5)->randomFloat(2, 0, 100),
            ]);
        });

        $this->info("  Updated {$count} visitors.");
    }

    protected function populateClusterHealthLevels(): void
    {
        $clusters = Cluster::whereNull('health_level')->get();
        $count = $clusters->count();

        if ($count === 0) {
            $this->line('No clusters need health level updates.');

            return;
        }

        $this->line("Updating {$count} clusters with health levels...");

        $clusters->each(function ($cluster) {
            $cluster->update([
                'health_level' => fake()->randomElement(ClusterHealthLevel::cases()),
            ]);
        });

        $this->info("  Updated {$count} clusters.");
    }

    protected function populateHouseholdEngagementLevels(): void
    {
        $households = Household::whereNull('engagement_level')->get();
        $count = $households->count();

        if ($count === 0) {
            $this->line('No households need engagement level updates.');

            return;
        }

        $this->line("Updating {$count} households with engagement levels...");

        $households->each(function ($household) {
            $household->update([
                'engagement_level' => fake()->randomElement(HouseholdEngagementLevel::cases()),
            ]);
        });

        $this->info("  Updated {$count} households.");
    }

    protected function createMemberActivities(): void
    {
        $existingCount = MemberActivity::count();

        if ($existingCount >= 10) {
            $this->line("Already have {$existingCount} member activities.");

            return;
        }

        $members = Member::take(25)->get();

        if ($members->isEmpty()) {
            $this->line('No members found for activity creation.');

            return;
        }

        $toCreate = min(25, $members->count());
        $this->line("Creating {$toCreate} member activities...");

        foreach ($members->take($toCreate) as $member) {
            MemberActivity::factory()->create([
                'member_id' => $member->id,
                'user_id' => null,
            ]);
        }

        $this->info("  Created {$toCreate} activities.");
    }

    protected function createVisitorFollowUps(): void
    {
        $existingCount = VisitorFollowUp::where('is_scheduled', true)->count();

        if ($existingCount >= 5) {
            $this->line("Already have {$existingCount} scheduled follow-ups.");

            return;
        }

        $visitors = Visitor::take(10)->get();

        if ($visitors->isEmpty()) {
            $this->line('No visitors found for follow-up creation.');

            return;
        }

        $toCreate = min(10, $visitors->count());
        $this->line("Creating {$toCreate} visitor follow-ups...");

        foreach ($visitors->take($toCreate) as $visitor) {
            VisitorFollowUp::factory()->scheduled()->create([
                'visitor_id' => $visitor->id,
            ]);
        }

        $this->info("  Created {$toCreate} follow-ups.");
    }

    protected function updateMemberNames(): void
    {
        $members = Member::all();

        if ($members->isEmpty()) {
            $this->line('No members found for name updates.');

            return;
        }

        $names = $this->getGhanaianNames();
        $count = 0;

        foreach ($members as $member) {
            $gender = fake()->randomElement(['male', 'female']);
            $firstName = fake()->randomElement($names[$gender.'_first']);
            $lastName = fake()->randomElement($names['last']);

            $member->update([
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);
            $count++;
        }

        $this->info("  Updated {$count} member names.");
    }

    protected function updateClusterNames(): void
    {
        $clusters = Cluster::all();

        if ($clusters->isEmpty()) {
            $this->line('No clusters found for name updates.');

            return;
        }

        $clusterNames = $this->getRealisticClusterNames();
        $count = 0;

        foreach ($clusters as $index => $cluster) {
            $name = $clusterNames[$index % count($clusterNames)];
            $cluster->update(['name' => $name]);
            $count++;
        }

        $this->info("  Updated {$count} cluster names.");
    }

    protected function createFinancialForecasts(string $branchId): void
    {
        $existingCount = FinancialForecast::where('branch_id', $branchId)->count();

        if ($existingCount >= 5) {
            $this->line("Already have {$existingCount} financial forecasts.");

            return;
        }

        $this->line('Creating financial forecasts...');

        // Create 4 monthly forecasts starting from current month
        for ($i = 0; $i < 4; $i++) {
            $periodStart = now()->startOfMonth()->addMonths($i);
            $periodEnd = $periodStart->copy()->endOfMonth();

            FinancialForecast::updateOrCreate(
                [
                    'branch_id' => $branchId,
                    'forecast_type' => 'monthly',
                    'period_start' => $periodStart,
                ],
                [
                    'period_end' => $periodEnd,
                    'predicted_total' => fake()->randomFloat(2, 15000, 50000),
                    'predicted_tithes' => fake()->randomFloat(2, 8000, 25000),
                    'predicted_offerings' => fake()->randomFloat(2, 5000, 15000),
                    'predicted_special' => fake()->randomFloat(2, 1000, 5000),
                    'predicted_other' => fake()->randomFloat(2, 500, 3000),
                    'confidence_lower' => fake()->randomFloat(2, 12000, 40000),
                    'confidence_upper' => fake()->randomFloat(2, 18000, 60000),
                    'confidence_score' => fake()->randomFloat(2, 65, 95),
                    'budget_target' => fake()->randomFloat(2, 20000, 55000),
                    'factors' => [
                        'historical_trend' => 'stable',
                        'seasonal_adjustment' => fake()->randomFloat(2, 0.9, 1.1),
                        'data_points_used' => fake()->numberBetween(8, 24),
                    ],
                ]
            );
        }

        // Create 1 quarterly forecast
        FinancialForecast::updateOrCreate(
            [
                'branch_id' => $branchId,
                'forecast_type' => 'quarterly',
                'period_start' => now()->startOfQuarter(),
            ],
            [
                'period_end' => now()->endOfQuarter(),
                'predicted_total' => fake()->randomFloat(2, 50000, 150000),
                'predicted_tithes' => fake()->randomFloat(2, 25000, 75000),
                'predicted_offerings' => fake()->randomFloat(2, 15000, 45000),
                'predicted_special' => fake()->randomFloat(2, 5000, 15000),
                'predicted_other' => fake()->randomFloat(2, 2000, 10000),
                'confidence_lower' => fake()->randomFloat(2, 45000, 130000),
                'confidence_upper' => fake()->randomFloat(2, 55000, 170000),
                'confidence_score' => fake()->randomFloat(2, 70, 92),
                'budget_target' => fake()->randomFloat(2, 60000, 160000),
                'factors' => [
                    'historical_trend' => 'growth',
                    'data_points_used' => 12,
                ],
            ]
        );

        $this->info('  Created 5 financial forecasts.');
    }

    protected function createAttendanceForecasts(string $branchId): void
    {
        $services = Service::where('branch_id', $branchId)->get();

        if ($services->isEmpty()) {
            $this->warn('  No services found for attendance forecasts.');

            return;
        }

        $existingCount = AttendanceForecast::where('branch_id', $branchId)->count();

        if ($existingCount >= 12) {
            $this->line("Already have {$existingCount} attendance forecasts.");

            return;
        }

        $this->line('Creating attendance forecasts...');

        $count = 0;
        // Create forecasts for next 4 weeks for each service
        foreach ($services as $service) {
            $dayOfWeek = $service->day_of_week ?? 0; // Default to Sunday

            for ($i = 0; $i < 4; $i++) {
                // Calculate the next occurrence of this day of week
                $forecastDate = now()->addWeeks($i);
                $currentDayOfWeek = $forecastDate->dayOfWeek;
                $daysToAdd = ($dayOfWeek - $currentDayOfWeek + 7) % 7;
                $forecastDate = $forecastDate->addDays($daysToAdd);

                AttendanceForecast::updateOrCreate(
                    [
                        'service_id' => $service->id,
                        'forecast_date' => $forecastDate->format('Y-m-d'),
                    ],
                    [
                        'branch_id' => $branchId,
                        'predicted_attendance' => fake()->numberBetween(80, 250),
                        'predicted_members' => fake()->numberBetween(60, 180),
                        'predicted_visitors' => fake()->numberBetween(8, 40),
                        'confidence_score' => fake()->randomFloat(2, 70, 95),
                        'factors' => [
                            'day_of_week' => $this->getDayName($dayOfWeek),
                            'historical_avg' => fake()->numberBetween(75, 200),
                            'trend' => fake()->randomElement(['stable', 'growing', 'declining']),
                        ],
                    ]
                );
                $count++;
            }
        }

        $this->info("  Created {$count} attendance forecasts.");
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function getGhanaianNames(): array
    {
        return [
            'male_first' => [
                'Kwame', 'Kofi', 'Kweku', 'Yaw', 'Kwabena', 'Kojo', 'Kwasi',
                'Emmanuel', 'Daniel', 'Samuel', 'Joseph', 'Michael', 'David',
                'Isaac', 'Benjamin', 'Joshua', 'Caleb', 'Nathaniel', 'Solomon',
                'Ebenezer', 'Francis', 'Charles', 'William', 'Richard', 'Patrick',
            ],
            'female_first' => [
                'Ama', 'Akua', 'Afia', 'Yaa', 'Abena', 'Adjoa', 'Akosua',
                'Grace', 'Mercy', 'Patience', 'Comfort', 'Felicia', 'Victoria',
                'Elizabeth', 'Mary', 'Ruth', 'Esther', 'Hannah', 'Deborah',
                'Priscilla', 'Rebecca', 'Sarah', 'Naomi', 'Miriam', 'Lydia',
            ],
            'last' => [
                'Mensah', 'Asante', 'Osei', 'Boateng', 'Owusu', 'Appiah',
                'Amoah', 'Adjei', 'Agyeman', 'Frimpong', 'Bonsu', 'Acheampong',
                'Badu', 'Ansah', 'Darko', 'Gyamfi', 'Ofori', 'Sarpong',
                'Tetteh', 'Nkrumah', 'Antwi', 'Yeboah', 'Amponsah', 'Asamoah',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function getRealisticClusterNames(): array
    {
        return [
            'Faith Builders Cell Group',
            'Overcomers House Fellowship',
            'Grace & Glory Cell',
            'Living Waters Fellowship',
            'Mountain Movers Cell Group',
            'Covenant Keepers Fellowship',
            'Light Bearers Cell',
            'Victorious Living Group',
            'Upper Room Fellowship',
            'Kingdom Builders Cell',
            'Joyful Hearts Fellowship',
            'New Dawn Cell Group',
        ];
    }

    protected function getDayName(int $dayOfWeek): string
    {
        return match ($dayOfWeek) {
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            default => 'Sunday',
        };
    }
}
