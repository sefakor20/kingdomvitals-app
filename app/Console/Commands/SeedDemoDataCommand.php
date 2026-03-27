<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ClusterHealthLevel;
use App\Enums\HouseholdEngagementLevel;
use App\Enums\LifecycleStage;
use App\Enums\PlanModule;
use App\Enums\RiskLevel;
use App\Enums\SmsEngagementLevel;
use App\Models\Domain;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Tenant\AiAlert;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Budget;
use App\Models\Tenant\Cluster;
use App\Models\Tenant\Donation;
use App\Models\Tenant\DutyRoster;
use App\Models\Tenant\EmailLog;
use App\Models\Tenant\Equipment;
use App\Models\Tenant\Event;
use App\Models\Tenant\Expense;
use App\Models\Tenant\Household;
use App\Models\Tenant\Member;
use App\Models\Tenant\MemberActivity;
use App\Models\Tenant\Pledge;
use App\Models\Tenant\PledgePrediction;
use App\Models\Tenant\PrayerRequest;
use App\Models\Tenant\Service;
use App\Models\Tenant\SmsLog;
use App\Models\Tenant\Visitor;
use App\Models\Tenant\VisitorFollowUp;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SeedDemoDataCommand extends Command
{
    protected $signature = 'tenant:seed-demo
                            {tenant : The tenant ID (UUID) or domain name}
                            {--modules=* : Specific modules to seed (default: all)}
                            {--count=1 : Multiplier for record quantities}
                            {--skip-enable-modules : Skip enabling all modules for the tenant}';

    protected $description = 'Seed demo data for a tenant with realistic test data for all or specific modules';

    /**
     * @var array<string, int>
     */
    protected array $summary = [];

    /**
     * Default record counts per module.
     *
     * @var array<string, int>
     */
    protected array $defaultCounts = [
        'members' => 50,
        'households' => 15,
        'clusters' => 5,
        'services' => 4,
        'visitors' => 20,
        'attendance' => 200,
        'donations' => 100,
        'expenses' => 30,
        'pledges' => 25,
        'budgets' => 6,
        'sms' => 75,
        'email' => 50,
        'equipment' => 20,
        'prayer_requests' => 15,
        'duty_roster' => 8,
        'events' => 10,
        'ai_insights' => 12,
    ];

    public function handle(): int
    {
        $tenant = $this->resolveTenant($this->argument('tenant'));

        if (! $tenant) {
            $this->error('Tenant not found.');

            return Command::FAILURE;
        }

        $this->info("Seeding demo data for tenant: {$tenant->name}");
        $this->newLine();

        try {
            tenancy()->initialize($tenant);

            DB::beginTransaction();

            if (! $this->option('skip-enable-modules')) {
                $this->enableAllModules($tenant);
            }

            $this->seedDemoData();

            DB::commit();

            tenancy()->end();

            $this->newLine();
            $this->info('Demo data seeding completed!');
            $this->displaySummary();

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            tenancy()->end();

            $this->error("Error seeding demo data: {$e->getMessage()}");
            $this->error("In: {$e->getFile()}:{$e->getLine()}");
            $this->line($e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    protected function resolveTenant(string $identifier): ?Tenant
    {
        // Try UUID lookup first
        $tenant = Tenant::find($identifier);

        if ($tenant) {
            return $tenant;
        }

        // Fall back to domain lookup
        $domain = Domain::where('domain', $identifier)->first();

        return $domain?->tenant;
    }

    protected function enableAllModules(Tenant $tenant): void
    {
        $plan = $tenant->subscriptionPlan;

        if (! $plan) {
            $this->warn('No subscription plan found. Assigning default plan with all modules.');

            $plan = SubscriptionPlan::where('is_default', true)->first()
                ?? SubscriptionPlan::first();

            if (! $plan) {
                $this->error('No subscription plans available. Please create one first.');

                return;
            }

            $tenant->update(['subscription_id' => $plan->id]);
            $tenant->refresh();
        }

        // Enable all modules by setting to null
        $plan->update(['enabled_modules' => null]);

        $this->info('All modules enabled for tenant.');
    }

    protected function getModulesToSeed(): array
    {
        $specifiedModules = $this->option('modules');

        if (empty($specifiedModules)) {
            return PlanModule::values();
        }

        return array_filter($specifiedModules, fn ($module) => in_array($module, PlanModule::values()));
    }

    protected function getCount(string $module): int
    {
        $multiplier = (int) $this->option('count') ?: 1;
        $baseCount = $this->defaultCounts[$module] ?? 10;

        return $baseCount * $multiplier;
    }

    protected function seedDemoData(): void
    {
        $modulesToSeed = $this->getModulesToSeed();

        // Phase 1: Foundation
        $branch = $this->seedBranch();

        // Phase 2: Core entities (depend on branch)
        $households = collect();
        $clusters = collect();
        $services = collect();

        if (in_array('households', $modulesToSeed)) {
            $households = $this->seedHouseholds($branch);
        }

        if (in_array('clusters', $modulesToSeed)) {
            $clusters = $this->seedClusters($branch);
        }

        if (in_array('services', $modulesToSeed)) {
            $services = $this->seedServices($branch);
        }

        // Phase 3: People (depend on branch, optionally household/cluster)
        $members = collect();
        $visitors = collect();

        if (in_array('members', $modulesToSeed) || in_array('children', $modulesToSeed)) {
            $members = $this->seedMembers($branch, $households, $clusters);
        }

        if (in_array('visitors', $modulesToSeed)) {
            $visitors = $this->seedVisitors($branch);
        }

        // Phase 4: Transactions & activities
        if (in_array('attendance', $modulesToSeed) && $services->isNotEmpty()) {
            $this->seedAttendance($branch, $members, $visitors, $services);
        }

        if (in_array('donations', $modulesToSeed)) {
            $this->seedDonations($branch, $members, $services);
        }

        if (in_array('expenses', $modulesToSeed)) {
            $this->seedExpenses($branch);
        }

        if (in_array('pledges', $modulesToSeed)) {
            $pledges = $this->seedPledges($branch, $members);
            $this->seedPledgePredictions($branch, $pledges);
        }

        if (in_array('budgets', $modulesToSeed)) {
            $this->seedBudgets($branch);
        }

        // Phase 5: Communication
        if (in_array('sms', $modulesToSeed)) {
            $this->seedSmsLogs($branch, $members);
        }

        if (in_array('email', $modulesToSeed)) {
            $this->seedEmailLogs($branch, $members);
        }

        // Phase 6: Ministry tools
        if (in_array('equipment', $modulesToSeed)) {
            $this->seedEquipment($branch, $members);
        }

        if (in_array('prayer_requests', $modulesToSeed)) {
            $this->seedPrayerRequests($branch, $members);
        }

        if (in_array('duty_roster', $modulesToSeed) && $services->isNotEmpty()) {
            $this->seedDutyRosters($branch, $services);
        }

        // Phase 7: Events & AI
        if (in_array('events', $modulesToSeed)) {
            $this->seedEvents($branch);
        }

        if (in_array('ai_insights', $modulesToSeed)) {
            $this->seedAiAlerts($branch, $members, $clusters);
        }

        // Phase 8: Dashboard widgets (follow-ups and activities)
        if (in_array('visitors', $modulesToSeed) && $visitors->isNotEmpty()) {
            $this->seedVisitorFollowUps($visitors);
        }

        if ((in_array('members', $modulesToSeed) || in_array('children', $modulesToSeed)) && $members->isNotEmpty()) {
            $this->seedMemberActivities($members);
        }
    }

    protected function seedBranch(): Branch
    {
        $existingBranch = Branch::where('is_main', true)->first();

        if ($existingBranch) {
            $this->line('Using existing main branch: '.$existingBranch->name);

            return $existingBranch;
        }

        $this->line('Creating main branch...');
        $branch = Branch::factory()->main()->create();
        $this->summary['Branch'] = 1;

        return $branch;
    }

    protected function seedHouseholds(Branch $branch): Collection
    {
        $count = $this->getCount('households');
        $this->line("Creating {$count} households...");

        $households = Household::factory()->count($count)->create([
            'branch_id' => $branch->id,
            'engagement_level' => fn () => fake()->randomElement(HouseholdEngagementLevel::cases()),
        ]);
        $this->summary['Households'] = $count;

        return $households;
    }

    protected function seedClusters(Branch $branch): Collection
    {
        $count = $this->getCount('clusters');
        $this->line("Creating {$count} clusters...");

        $clusters = Cluster::factory()
            ->count($count)
            ->create([
                'branch_id' => $branch->id,
                'health_level' => fn () => fake()->randomElement(ClusterHealthLevel::cases()),
            ]);

        $this->summary['Clusters'] = $count;

        return $clusters;
    }

    protected function seedServices(Branch $branch): Collection
    {
        $count = $this->getCount('services');
        $this->line("Creating {$count} services...");

        $services = collect();

        // Create standard service types
        $services->push(Service::factory()->sunday()->create(['branch_id' => $branch->id]));
        $services->push(Service::factory()->midweek()->create(['branch_id' => $branch->id]));

        if ($count > 2) {
            $services->push(Service::factory()->prayer()->create(['branch_id' => $branch->id]));
        }

        if ($count > 3) {
            $services->push(Service::factory()->youth()->create(['branch_id' => $branch->id]));
        }

        // Fill remaining with random services
        $remaining = $count - $services->count();
        if ($remaining > 0) {
            $additionalServices = Service::factory()
                ->count($remaining)
                ->create(['branch_id' => $branch->id]);
            $services = $services->merge($additionalServices);
        }

        $this->summary['Services'] = $services->count();

        return $services;
    }

    protected function seedMembers(Branch $branch, Collection $households, Collection $clusters): Collection
    {
        $count = $this->getCount('members');
        $this->line("Creating {$count} members...");

        $members = collect();

        // Create adults (70%)
        $adultsCount = (int) ($count * 0.7);

        Member::factory()
            ->count($adultsCount)
            ->create([
                'primary_branch_id' => $branch->id,
                'household_id' => fn () => $households->isNotEmpty() ? $households->random()->id : null,
            ])
            ->each(function ($member) use ($clusters, $members) {
                // Assign some members to clusters
                if (fake()->boolean(40) && $clusters->isNotEmpty()) {
                    $member->clusters()->attach(
                        $clusters->random()->id,
                        ['joined_at' => now()->subDays(rand(1, 365))]
                    );
                }
                $members->push($member);
            });

        // Create children (30%) - younger date_of_birth
        $childrenCount = $count - $adultsCount;

        Member::factory()
            ->count($childrenCount)
            ->create([
                'primary_branch_id' => $branch->id,
                'household_id' => fn () => $households->isNotEmpty() ? $households->random()->id : null,
                'date_of_birth' => fn () => fake()->dateTimeBetween('-17 years', '-1 year'),
            ])
            ->each(fn ($m) => $members->push($m));

        // Update members with AI-related fields for dashboard widgets
        $members->each(function ($member) {
            $capacityScore = fake()->randomFloat(2, 20, 95);
            $currentAnnualGiving = fake()->randomFloat(2, 500, 15000);
            $potentialGap = $capacityScore < 70
                ? fake()->randomFloat(2, 500, 5000)
                : fake()->randomFloat(2, 0, 500);

            $member->update([
                'churn_risk_score' => fake()->optional(0.3)->randomFloat(2, 0, 100),
                'lifecycle_stage' => fake()->randomElement(LifecycleStage::cases()),
                'sms_engagement_level' => fake()->randomElement(SmsEngagementLevel::cases()),
                'attendance_anomaly_detected_at' => fake()->optional(0.1)->dateTimeBetween('-7 days', 'now'),
                // Giving intelligence fields
                'giving_capacity_score' => $capacityScore,
                'giving_potential_gap' => $potentialGap,
                'giving_capacity_factors' => [
                    'profession_weight' => fake()->randomFloat(2, 0.1, 0.4),
                    'employment_status' => $member->employment_status?->value ?? 'employed',
                    'historical_trajectory' => fake()->randomElement(['growth', 'stable', 'declining']),
                    'lifecycle_multiplier' => fake()->randomFloat(2, 0.8, 1.2),
                    'current_annual_giving' => $currentAnnualGiving,
                ],
                'giving_capacity_analyzed_at' => now()->subDays(fake()->numberBetween(1, 7)),
                'giving_consistency_score' => fake()->numberBetween(30, 100),
                'giving_growth_rate' => fake()->randomFloat(2, -15, 25),
                'donor_tier' => fake()->randomElement(['top_10', 'top_25', 'top_50', 'regular', 'new', 'lapsed']),
                'giving_trend' => fake()->randomElement(['growing', 'stable', 'declining', 'new', 'lapsed']),
                'giving_analyzed_at' => now()->subDays(fake()->numberBetween(1, 7)),
            ]);
        });

        $this->summary['Members'] = $members->count();

        return $members;
    }

    protected function seedVisitors(Branch $branch): Collection
    {
        $count = $this->getCount('visitors');
        $this->line("Creating {$count} visitors...");

        $visitors = Visitor::factory()
            ->count($count)
            ->create([
                'branch_id' => $branch->id,
                'conversion_score' => fn () => fake()->optional(0.5)->randomFloat(2, 0, 100),
            ]);

        $this->summary['Visitors'] = $count;

        return $visitors;
    }

    protected function seedAttendance(Branch $branch, Collection $members, Collection $visitors, Collection $services): void
    {
        $count = $this->getCount('attendance');
        $this->line("Creating {$count} attendance records...");

        $created = 0;
        $usedCombinations = [];

        // Generate unique dates for the last 30 days
        $dates = collect(range(0, 29))->map(fn ($days) => now()->subDays($days)->format('Y-m-d'))->toArray();

        // Attendance for members (80% of records)
        $memberCount = min((int) ($count * 0.8), $members->count() * count($dates));
        if ($memberCount > 0 && $members->isNotEmpty()) {
            $attempts = 0;
            while ($created < $memberCount && $attempts < $memberCount * 3) {
                $memberId = $members->random()->id;
                $serviceId = $services->random()->id;
                $date = $dates[array_rand($dates)];
                $key = "{$memberId}-{$serviceId}-{$date}";

                if (! isset($usedCombinations[$key])) {
                    $usedCombinations[$key] = true;
                    Attendance::factory()->create([
                        'branch_id' => $branch->id,
                        'service_id' => $serviceId,
                        'member_id' => $memberId,
                        'visitor_id' => null,
                        'date' => $date,
                    ]);
                    $created++;
                }
                $attempts++;
            }
        }

        // Attendance for visitors (remaining records)
        $visitorCount = min($count - $created, $visitors->count() * count($dates));
        if ($visitorCount > 0 && $visitors->isNotEmpty()) {
            $visitorAttempts = 0;
            $visitorCreated = 0;
            while ($visitorCreated < $visitorCount && $visitorAttempts < $visitorCount * 3) {
                $visitorId = $visitors->random()->id;
                $serviceId = $services->random()->id;
                $date = $dates[array_rand($dates)];
                $key = "v-{$visitorId}-{$serviceId}-{$date}";

                if (! isset($usedCombinations[$key])) {
                    $usedCombinations[$key] = true;
                    Attendance::factory()->create([
                        'branch_id' => $branch->id,
                        'service_id' => $serviceId,
                        'member_id' => null,
                        'visitor_id' => $visitorId,
                        'date' => $date,
                    ]);
                    $created++;
                    $visitorCreated++;
                }
                $visitorAttempts++;
            }
        }

        $this->summary['Attendance'] = $created;
    }

    protected function seedDonations(Branch $branch, Collection $members, Collection $services): void
    {
        $count = $this->getCount('donations');
        $this->line("Creating {$count} donations...");

        for ($i = 0; $i < $count; $i++) {
            $isAnonymous = fake()->boolean(15);

            Donation::factory()
                ->when($isAnonymous, fn ($f) => $f->anonymous())
                ->when(! $isAnonymous && fake()->boolean(80), fn ($f) => $f->tithe())
                ->create([
                    'branch_id' => $branch->id,
                    'member_id' => $isAnonymous ? null : ($members->isNotEmpty() ? $members->random()->id : null),
                    'service_id' => $services->isNotEmpty() && fake()->boolean(60) ? $services->random()->id : null,
                ]);
        }

        $this->summary['Donations'] = $count;
    }

    protected function seedExpenses(Branch $branch): void
    {
        $count = $this->getCount('expenses');
        $this->line("Creating {$count} expenses...");

        Expense::factory()->count($count)->create(['branch_id' => $branch->id]);

        $this->summary['Expenses'] = $count;
    }

    protected function seedPledges(Branch $branch, Collection $members): Collection
    {
        $count = $this->getCount('pledges');
        $this->line("Creating {$count} pledges...");

        $pledges = Pledge::factory()
            ->count($count)
            ->create([
                'branch_id' => $branch->id,
                'member_id' => fn () => $members->isNotEmpty() ? $members->random()->id : null,
            ]);

        $this->summary['Pledges'] = $count;

        return $pledges;
    }

    protected function seedPledgePredictions(Branch $branch, Collection $pledges): void
    {
        if ($pledges->isEmpty()) {
            return;
        }

        $this->line('Creating pledge predictions...');
        $count = 0;

        foreach ($pledges as $pledge) {
            // Calculate fulfillment based on pledge status and amount
            $fulfillmentPercent = $pledge->amount > 0
                ? ($pledge->amount_fulfilled / $pledge->amount) * 100
                : 0;

            // Predict probability based on current fulfillment
            $probability = match (true) {
                $fulfillmentPercent >= 80 => fake()->randomFloat(2, 75, 98),
                $fulfillmentPercent >= 50 => fake()->randomFloat(2, 50, 80),
                $fulfillmentPercent >= 25 => fake()->randomFloat(2, 30, 60),
                default => fake()->randomFloat(2, 10, 45),
            };

            $riskLevel = RiskLevel::fromFulfillmentProbability($probability);

            PledgePrediction::create([
                'branch_id' => $branch->id,
                'pledge_id' => $pledge->id,
                'member_id' => $pledge->member_id,
                'fulfillment_probability' => $probability,
                'risk_level' => $riskLevel,
                'recommended_nudge_at' => $riskLevel->shouldSendNudge()
                    ? now()->addDays(fake()->numberBetween(1, 14))
                    : null,
                'factors' => [
                    'fulfillment_pace' => fake()->randomElement(['ahead', 'on_track', 'behind', 'far_behind']),
                    'pledge_history_completion_rate' => fake()->randomFloat(2, 0.5, 1.0),
                    'giving_trend' => fake()->randomElement(['growing', 'stable', 'declining']),
                    'days_since_last_payment' => fake()->numberBetween(1, 60),
                ],
                'provider' => 'heuristic',
            ]);
            $count++;
        }

        $this->summary['Pledge Predictions'] = $count;
    }

    protected function seedBudgets(Branch $branch): void
    {
        $count = $this->getCount('budgets');
        $this->line("Creating {$count} budgets...");

        Budget::factory()->count($count)->thisYear()->create(['branch_id' => $branch->id]);

        $this->summary['Budgets'] = $count;
    }

    protected function seedSmsLogs(Branch $branch, Collection $members): void
    {
        $count = $this->getCount('sms');
        $this->line("Creating {$count} SMS logs...");

        SmsLog::factory()
            ->count($count)
            ->create([
                'branch_id' => $branch->id,
                'member_id' => fn () => $members->isNotEmpty() && fake()->boolean(70) ? $members->random()->id : null,
            ]);

        $this->summary['SMS Logs'] = $count;
    }

    protected function seedEmailLogs(Branch $branch, Collection $members): void
    {
        $count = $this->getCount('email');
        $this->line("Creating {$count} email logs...");

        EmailLog::factory()
            ->count($count)
            ->create([
                'branch_id' => $branch->id,
                'member_id' => fn () => $members->isNotEmpty() && fake()->boolean(70) ? $members->random()->id : null,
            ]);

        $this->summary['Email Logs'] = $count;
    }

    protected function seedEquipment(Branch $branch, Collection $members): void
    {
        $count = $this->getCount('equipment');
        $this->line("Creating {$count} equipment items...");

        Equipment::factory()
            ->count($count)
            ->create([
                'branch_id' => $branch->id,
                'assigned_to' => fn () => $members->isNotEmpty() && fake()->boolean(30) ? $members->random()->id : null,
            ]);

        $this->summary['Equipment'] = $count;
    }

    protected function seedPrayerRequests(Branch $branch, Collection $members): void
    {
        $count = $this->getCount('prayer_requests');
        $this->line("Creating {$count} prayer requests...");

        PrayerRequest::factory()
            ->count($count)
            ->create([
                'branch_id' => $branch->id,
                'member_id' => fn () => $members->isNotEmpty() && fake()->boolean(80) ? $members->random()->id : null,
            ]);

        $this->summary['Prayer Requests'] = $count;
    }

    protected function seedDutyRosters(Branch $branch, Collection $services): void
    {
        $count = $this->getCount('duty_roster');
        $this->line("Creating {$count} duty rosters...");

        DutyRoster::factory()
            ->count($count)
            ->create([
                'branch_id' => $branch->id,
                'service_id' => fn () => $services->random()->id,
            ]);

        $this->summary['Duty Rosters'] = $count;
    }

    protected function seedEvents(Branch $branch): void
    {
        $count = $this->getCount('events');
        $this->line("Creating {$count} events...");

        // Mix of upcoming and past events
        $upcomingCount = (int) ($count * 0.6);
        $pastCount = $count - $upcomingCount;

        Event::factory()->count($upcomingCount)->upcoming()->create(['branch_id' => $branch->id]);
        Event::factory()->count($pastCount)->past()->create(['branch_id' => $branch->id]);

        $this->summary['Events'] = $count;
    }

    protected function seedAiAlerts(Branch $branch, Collection $members, Collection $clusters): void
    {
        $count = $this->getCount('ai_insights');
        $this->line("Creating {$count} AI alerts...");

        $created = 0;

        // Churn risk alerts for members
        if ($members->isNotEmpty()) {
            $churnCount = min((int) ($count * 0.3), $members->count());
            for ($i = 0; $i < $churnCount; $i++) {
                AiAlert::factory()
                    ->churnRisk()
                    ->create([
                        'branch_id' => $branch->id,
                        'alertable_type' => Member::class,
                        'alertable_id' => $members->random()->id,
                    ]);
                $created++;
            }
        }

        // Cluster health alerts
        if ($clusters->isNotEmpty()) {
            $clusterCount = min((int) ($count * 0.2), $clusters->count());
            for ($i = 0; $i < $clusterCount; $i++) {
                AiAlert::factory()
                    ->clusterHealth()
                    ->create([
                        'branch_id' => $branch->id,
                        'alertable_type' => Cluster::class,
                        'alertable_id' => $clusters->random()->id,
                    ]);
                $created++;
            }
        }

        // Generic alerts for remaining
        $remaining = $count - $created;
        if ($remaining > 0 && $members->isNotEmpty()) {
            for ($i = 0; $i < $remaining; $i++) {
                AiAlert::factory()
                    ->attendanceAnomaly()
                    ->create([
                        'branch_id' => $branch->id,
                        'alertable_type' => Member::class,
                        'alertable_id' => $members->random()->id,
                    ]);
                $created++;
            }
        }

        $this->summary['AI Alerts'] = $created;
    }

    protected function seedVisitorFollowUps(Collection $visitors): void
    {
        $count = 10;
        $this->line("Creating {$count} visitor follow-ups...");

        foreach (range(1, $count) as $i) {
            VisitorFollowUp::factory()
                ->scheduled()
                ->create([
                    'visitor_id' => $visitors->random()->id,
                ]);
        }

        $this->summary['Follow-ups'] = $count;
    }

    protected function seedMemberActivities(Collection $members): void
    {
        $count = 25;
        $this->line("Creating {$count} member activities...");

        foreach (range(1, $count) as $i) {
            MemberActivity::factory()
                ->create([
                    'member_id' => $members->random()->id,
                    'user_id' => null,
                ]);
        }

        $this->summary['Activities'] = $count;
    }

    protected function displaySummary(): void
    {
        $this->newLine();
        $this->info('Summary:');

        $rows = [];
        foreach ($this->summary as $entity => $count) {
            $rows[] = [$entity, $count];
        }

        $this->table(['Entity', 'Records Created'], $rows);
    }
}
