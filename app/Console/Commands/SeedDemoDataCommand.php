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

        $clusterData = $this->getRealisticClusterData();
        $clusters = collect();

        for ($i = 0; $i < $count; $i++) {
            $data = $clusterData[$i % count($clusterData)];
            $cluster = Cluster::factory()->create([
                'branch_id' => $branch->id,
                'name' => $data['name'],
                'description' => $data['description'],
                'health_level' => fake()->randomElement(ClusterHealthLevel::cases()),
            ]);
            $clusters->push($cluster);
        }

        $this->summary['Clusters'] = $count;

        return $clusters;
    }

    protected function seedServices(Branch $branch): Collection
    {
        $count = $this->getCount('services');
        $this->line("Creating {$count} services...");

        $serviceNames = $this->getRealisticServiceNames();
        $services = collect();

        for ($i = 0; $i < min($count, count($serviceNames)); $i++) {
            $service = Service::factory()->create([
                'branch_id' => $branch->id,
                'name' => $serviceNames[$i],
            ]);
            $services->push($service);
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

        $messages = $this->getRealisticSmsMessages();

        for ($i = 0; $i < $count; $i++) {
            SmsLog::factory()->create([
                'branch_id' => $branch->id,
                'member_id' => $members->isNotEmpty() && fake()->boolean(70) ? $members->random()->id : null,
                'message' => $messages[$i % count($messages)],
            ]);
        }

        $this->summary['SMS Logs'] = $count;
    }

    protected function seedEmailLogs(Branch $branch, Collection $members): void
    {
        $count = $this->getCount('email');
        $this->line("Creating {$count} email logs...");

        $emailData = $this->getRealisticEmailData();

        for ($i = 0; $i < $count; $i++) {
            $data = $emailData[$i % count($emailData)];
            EmailLog::factory()->create([
                'branch_id' => $branch->id,
                'member_id' => $members->isNotEmpty() && fake()->boolean(70) ? $members->random()->id : null,
                'subject' => $data['subject'],
                'body' => $data['body'],
            ]);
        }

        $this->summary['Email Logs'] = $count;
    }

    protected function seedEquipment(Branch $branch, Collection $members): void
    {
        $count = $this->getCount('equipment');
        $this->line("Creating {$count} equipment items...");

        $equipmentData = $this->getRealisticEquipmentData();

        for ($i = 0; $i < $count; $i++) {
            $data = $equipmentData[$i % count($equipmentData)];
            Equipment::factory()->create([
                'branch_id' => $branch->id,
                'name' => $data['name'],
                'description' => $data['description'],
                'assigned_to' => $members->isNotEmpty() && fake()->boolean(30) ? $members->random()->id : null,
            ]);
        }

        $this->summary['Equipment'] = $count;
    }

    protected function seedPrayerRequests(Branch $branch, Collection $members): void
    {
        $count = $this->getCount('prayer_requests');
        $this->line("Creating {$count} prayer requests...");

        $prayerDescriptions = $this->getRealisticPrayerRequests();

        for ($i = 0; $i < $count; $i++) {
            PrayerRequest::factory()->create([
                'branch_id' => $branch->id,
                'member_id' => $members->isNotEmpty() && fake()->boolean(80) ? $members->random()->id : null,
                'description' => $prayerDescriptions[$i % count($prayerDescriptions)],
            ]);
        }

        $this->summary['Prayer Requests'] = $count;
    }

    protected function seedDutyRosters(Branch $branch, Collection $services): void
    {
        $count = $this->getCount('duty_roster');
        $this->line("Creating {$count} duty rosters...");

        $themes = $this->getRealisticDutyThemes();
        $remarks = $this->getRealisticDutyRemarks();

        for ($i = 0; $i < $count; $i++) {
            DutyRoster::factory()->create([
                'branch_id' => $branch->id,
                'service_id' => $services->random()->id,
                'theme' => $themes[$i % count($themes)],
                'remarks' => fake()->boolean(40) ? $remarks[array_rand($remarks)] : null,
            ]);
        }

        $this->summary['Duty Rosters'] = $count;
    }

    protected function seedEvents(Branch $branch): void
    {
        $count = $this->getCount('events');
        $this->line("Creating {$count} events...");

        $eventData = $this->getRealisticEventData();
        $upcomingCount = (int) ($count * 0.6);

        for ($i = 0; $i < $count; $i++) {
            $data = $eventData[$i % count($eventData)];
            $isUpcoming = $i < $upcomingCount;

            Event::factory()
                ->when($isUpcoming, fn ($f) => $f->upcoming())
                ->when(! $isUpcoming, fn ($f) => $f->past())
                ->create([
                    'branch_id' => $branch->id,
                    'name' => $data['name'],
                    'description' => $data['description'],
                ]);
        }

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

    // ==========================================
    // REALISTIC CONTENT HELPERS
    // ==========================================

    /**
     * @return array<int, string>
     */
    protected function getRealisticServiceNames(): array
    {
        return [
            'Sunday Worship Service',
            'Midweek Bible Study',
            'Friday Prayer Meeting',
            'Youth Fellowship',
            'Children\'s Church',
            'Dawn Broadcast Service',
            'Communion Service',
            'Evangelism Outreach',
            'Women\'s Fellowship',
            'Men\'s Fellowship',
        ];
    }

    /**
     * @return array<int, array{name: string, description: string}>
     */
    protected function getRealisticClusterData(): array
    {
        return [
            ['name' => 'Faith Builders Cell Group', 'description' => 'Weekly Bible study and prayer meeting focused on building strong foundations of faith.'],
            ['name' => 'Living Waters Fellowship', 'description' => 'A vibrant community of believers meeting for worship, teaching, and fellowship.'],
            ['name' => 'Grace & Glory Cell', 'description' => 'Home fellowship group dedicated to experiencing God\'s grace and glory together.'],
            ['name' => 'Overcomers House Fellowship', 'description' => 'Empowering believers to overcome challenges through faith and community support.'],
            ['name' => 'Mountain Movers Cell Group', 'description' => 'Prayer-focused group committed to moving mountains through faith.'],
            ['name' => 'Covenant Keepers Fellowship', 'description' => 'Growing in covenant relationship with God and one another.'],
            ['name' => 'Light Bearers Cell', 'description' => 'Shining the light of Christ in our community through service and witness.'],
            ['name' => 'Upper Room Fellowship', 'description' => 'Seeking the presence of God like the early disciples in the upper room.'],
            ['name' => 'Kingdom Builders Cell', 'description' => 'Advancing God\'s kingdom through discipleship and outreach.'],
            ['name' => 'New Dawn Fellowship', 'description' => 'A new beginning for believers seeking spiritual renewal and growth.'],
        ];
    }

    /**
     * @return array<int, array{name: string, description: string}>
     */
    protected function getRealisticEventData(): array
    {
        return [
            ['name' => 'Annual Thanksgiving Service', 'description' => 'Join us for a special thanksgiving celebration as we count our blessings and give glory to God for His faithfulness throughout the year.'],
            ['name' => 'Youth Conference 2024', 'description' => 'A two-day conference for young people featuring worship, teaching, and fellowship. Theme: "Rising to Shine" - empowering the next generation.'],
            ['name' => 'Easter Resurrection Service', 'description' => 'Celebrate the risen Christ with us in a powerful sunrise service followed by fellowship breakfast. All are welcome!'],
            ['name' => 'Leadership Development Summit', 'description' => 'Equipping church leaders with practical tools for effective ministry. Sessions on vision casting, team building, and spiritual leadership.'],
            ['name' => 'Marriage Enrichment Seminar', 'description' => 'Strengthen your marriage with biblical principles. Topics include communication, conflict resolution, and keeping the spark alive.'],
            ['name' => 'Women\'s Ministry Retreat', 'description' => 'A weekend of refreshing for women. Experience worship, testimonies, and sisterhood in a beautiful retreat setting.'],
            ['name' => 'Men\'s Fellowship Breakfast', 'description' => 'Monthly gathering for men to fellowship, pray, and grow together. Guest speaker and hearty breakfast included.'],
            ['name' => 'Christmas Carol Service', 'description' => 'A beautiful evening of carols, scripture readings, and celebration of Christ\'s birth. Candlelight service with choir performances.'],
            ['name' => 'Harvest Celebration', 'description' => 'Celebrating God\'s provision and faithfulness. Bring your harvest offerings and join us for a joyful service.'],
            ['name' => 'New Members Orientation', 'description' => 'Welcome to the family! Learn about our church vision, values, and how to get connected in ministry.'],
            ['name' => 'Community Outreach Day', 'description' => 'Serving our community with love. Activities include free health screening, food distribution, and children\'s programs.'],
            ['name' => 'Prayer & Fasting Conference', 'description' => 'Three days of intensive prayer and fasting for breakthrough. Multiple prayer sessions and teaching on spiritual warfare.'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function getRealisticPrayerRequests(): array
    {
        return [
            'Please pray for healing and strength during my recovery from surgery. I trust in God\'s faithfulness and believe for complete restoration.',
            'Asking for wisdom and guidance as I make important career decisions. Seeking God\'s direction for the next chapter of my life.',
            'Prayer for my family\'s spiritual growth and unity. May we grow closer to God and to each other.',
            'Requesting prayer for financial breakthrough. Trusting God to open doors of opportunity and provision.',
            'Please pray for my children\'s education and protection. May they excel in their studies and remain safe.',
            'Seeking prayer for the salvation of my loved ones. May their hearts be softened to receive Christ.',
            'Prayer for peace and restoration in my marriage. Asking God to heal wounds and renew our love.',
            'Please pray for safe travel mercies as I embark on a journey. May God\'s angels guard and protect me.',
            'Requesting prayer for upcoming surgery. Asking for the doctors\' skill and God\'s healing touch.',
            'Prayer for deliverance from anxiety and fear. Trusting God\'s perfect love to cast out all fear.',
            'Please pray for my business to prosper. Seeking God\'s blessing and favor in my endeavors.',
            'Asking for prayer regarding a difficult relationship. May God bring reconciliation and peace.',
            'Prayer for strength to overcome temptation. Asking for the Holy Spirit\'s power to resist.',
            'Please pray for direction in ministry. Seeking clarity about where God wants me to serve.',
            'Requesting prayer for my health condition. Believing God for healing and supernatural intervention.',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function getRealisticDutyThemes(): array
    {
        return [
            'Walking in Faith',
            'The Power of Prayer',
            'God\'s Unfailing Love',
            'Living in Victory',
            'The Joy of Salvation',
            'Grace and Mercy',
            'Standing on the Promises',
            'The Shepherd\'s Care',
            'Renewed Strength',
            'Hope in Christ',
            'The Fruit of the Spirit',
            'Trusting God\'s Plan',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function getRealisticDutyRemarks(): array
    {
        return [
            'Guest preacher from partner church',
            'Special music ministry presentation',
            'Communion service scheduled',
            'Children\'s dedication during service',
            'Youth choir leading worship',
            'Testimony session included',
            'New members welcome',
            'Prayer and anointing service',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function getRealisticSmsMessages(): array
    {
        return [
            'Reminder: Sunday service starts at 9:00 AM. See you there! God bless.',
            'Happy birthday! May the Lord bless you with health, joy, and prosperity this new year.',
            'Prayer meeting this Friday at 6:00 PM. Your presence is encouraged. Come expecting!',
            'Don\'t forget: Midweek Bible Study tomorrow at 6:30 PM. Bring your Bible!',
            'Thank you for visiting our church! We hope to see you again soon. God bless.',
            'Youth Fellowship this Saturday at 4:00 PM. Invite a friend and join us!',
            'Reminder: Choir rehearsal tonight at 7:00 PM. All choir members please attend.',
            'Special announcement: Church anniversary celebration next Sunday. Don\'t miss it!',
            'Good morning! Wishing you a blessed week ahead. The Lord is with you.',
            'Thank you for your faithful giving. Your generosity is making a difference!',
            'Women\'s fellowship meeting this Saturday at 3:00 PM. All ladies welcome!',
            'Men\'s breakfast this Sunday after service. Join us for fellowship and food.',
        ];
    }

    /**
     * @return array<int, array{subject: string, body: string}>
     */
    protected function getRealisticEmailData(): array
    {
        return [
            [
                'subject' => 'Weekly Service Update - Join Us This Sunday!',
                'body' => '<p>Dear beloved member,</p><p>We are excited to invite you to our Sunday worship service. This week\'s message will focus on "Walking in God\'s Purpose." Come expecting a powerful time of worship and the Word!</p><p>Service times: 7:00 AM (First Service) and 9:30 AM (Second Service)</p><p>Looking forward to worshipping with you!</p><p>Blessings,<br>The Church Team</p>',
            ],
            [
                'subject' => 'Important Announcement - Upcoming Church Events',
                'body' => '<p>Dear church family,</p><p>We have several exciting events coming up that we don\'t want you to miss:</p><ul><li>Youth Conference - Next Saturday</li><li>Women\'s Fellowship - Every Tuesday at 5:00 PM</li><li>Leadership Training - Monthly on first Saturdays</li></ul><p>Mark your calendars and plan to attend. Your participation strengthens our community!</p><p>God bless you,<br>Church Administration</p>',
            ],
            [
                'subject' => 'Thank You for Your Generous Giving',
                'body' => '<p>Dear faithful giver,</p><p>We want to express our heartfelt gratitude for your generous contribution to the church. Your giving is making a real difference in our ministry and outreach efforts.</p><p>May the Lord bless you abundantly as you continue to sow into His kingdom!</p><p>With appreciation,<br>Finance Team</p>',
            ],
            [
                'subject' => 'Prayer Request Follow-up',
                'body' => '<p>Dear member,</p><p>We wanted to follow up on your prayer request submitted last week. Our prayer team has been lifting you up in prayer. We believe God is working on your behalf!</p><p>Please don\'t hesitate to reach out if you need additional support or would like us to continue praying.</p><p>In Christ\'s love,<br>Prayer Ministry Team</p>',
            ],
            [
                'subject' => 'Welcome to Our Church Family!',
                'body' => '<p>Dear new member,</p><p>Welcome to the family! We are thrilled to have you join our church community. Here are a few ways to get connected:</p><ul><li>Attend our New Members Class</li><li>Join a Cell Group</li><li>Explore volunteer opportunities</li></ul><p>If you have any questions, please don\'t hesitate to reach out. We\'re here to help you grow in your faith journey!</p><p>With joy,<br>Membership Team</p>',
            ],
            [
                'subject' => 'Ministry Volunteer Opportunity',
                'body' => '<p>Dear member,</p><p>We have exciting volunteer opportunities available in various ministries. Whether you\'re passionate about children, worship, ushering, or outreach, there\'s a place for you to serve!</p><p>Serving is a wonderful way to use your God-given gifts and connect with others. Contact us to learn more about how you can get involved.</p><p>Blessings,<br>Ministry Coordination Team</p>',
            ],
            [
                'subject' => 'Monthly Newsletter - Highlights and Updates',
                'body' => '<p>Dear church family,</p><p>Here are the highlights from this month:</p><p><strong>Testimonies:</strong> We celebrated 5 new baptisms and 12 new members this month!</p><p><strong>Outreach:</strong> Our community service reached over 200 families.</p><p><strong>Upcoming:</strong> Don\'t miss our annual conference next month.</p><p>Thank you for being part of this amazing community!</p><p>God bless,<br>Communications Team</p>',
            ],
            [
                'subject' => 'Reminder: Cell Group Meeting This Week',
                'body' => '<p>Dear cell group member,</p><p>This is a friendly reminder that our cell group meeting is scheduled for this week. We\'ll be continuing our study series and having a time of prayer and fellowship.</p><p>Please bring your Bible and come ready to share. Light refreshments will be served.</p><p>See you there!<br>Your Cell Group Leader</p>',
            ],
        ];
    }

    /**
     * @return array<int, array{name: string, description: string}>
     */
    protected function getRealisticEquipmentData(): array
    {
        return [
            ['name' => 'Shure SM58 Microphone', 'description' => 'Professional dynamic microphone for sermon delivery and announcements.'],
            ['name' => 'Yamaha MG16XU Mixer', 'description' => 'Digital sound mixer with 16 channels for worship team audio management.'],
            ['name' => 'Epson PowerLite Projector', 'description' => 'HD projector for sanctuary display screens and presentations.'],
            ['name' => 'JBL EON615 Speaker', 'description' => 'Portable powered speaker system for outdoor events and conferences.'],
            ['name' => 'Roland TD-17 Drum Kit', 'description' => 'Electronic drum kit for worship team with silent practice capability.'],
            ['name' => 'Yamaha P-125 Digital Piano', 'description' => 'Weighted key digital piano for worship services and music ministry.'],
            ['name' => 'Canon EOS Camera', 'description' => 'DSLR camera for church events photography and video recording.'],
            ['name' => 'Sennheiser Wireless System', 'description' => 'Wireless microphone system for pastors and worship leaders.'],
            ['name' => 'LED Stage Lighting Set', 'description' => 'Professional LED lighting for sanctuary and event ambiance.'],
            ['name' => 'Audio-Technica Headphones', 'description' => 'Monitoring headphones for sound technicians and musicians.'],
            ['name' => 'Communion Set (Silver)', 'description' => 'Complete communion set including trays and cups for 500 members.'],
            ['name' => 'Baptismal Robes Set', 'description' => 'Set of 10 white baptismal robes in various sizes.'],
            ['name' => 'Portable PA System', 'description' => 'All-in-one portable sound system for outdoor ministry and outreach.'],
            ['name' => 'Video Camera Tripod', 'description' => 'Professional tripod for stable video recording during services.'],
            ['name' => 'Choir Microphones (Set of 4)', 'description' => 'Condenser microphones designed for choir and vocal group recording.'],
            ['name' => 'Folding Chairs (Set of 50)', 'description' => 'Comfortable padded folding chairs for overflow seating.'],
            ['name' => 'Pulpit/Lectern', 'description' => 'Wooden pulpit with built-in microphone stand and lighting.'],
            ['name' => 'Sound Booth Equipment Rack', 'description' => 'Professional equipment rack for organizing audio gear.'],
            ['name' => 'Wireless In-Ear Monitors', 'description' => 'In-ear monitoring system for worship band members.'],
            ['name' => 'Generator (5KVA)', 'description' => 'Backup power generator for uninterrupted services during outages.'],
        ];
    }
}
