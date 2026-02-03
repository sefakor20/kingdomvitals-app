# Codebase Structure

**Analysis Date:** 2026-02-03

## Directory Layout

```
kingdomvitals-app/
├── app/                           # Application source code
│   ├── Actions/                   # Fortify authentication actions
│   │   └── Fortify/               # CreateNewUser, ResetUserPassword, PasswordValidationRules
│   ├── Console/                   # Artisan commands (auto-registered in Laravel 12)
│   │   └── Commands/
│   ├── Enums/                     # Type-safe enums (MembershipStatus, BillingCycle, etc.)
│   ├── Exports/                   # Excel export classes
│   ├── Http/
│   │   ├── Controllers/           # HTTP request handlers
│   │   │   ├── SuperAdmin/        # Super admin only controllers
│   │   │   │   └── Auth/          # SuperAdmin authentication
│   │   │   ├── Tenant/            # Tenant-scoped controllers
│   │   │   └── Webhooks/          # Webhook handlers (Paystack, TextTango)
│   │   ├── Middleware/            # Request middleware
│   │   └── Requests/              # Form validation requests
│   ├── Imports/                   # Excel import classes
│   ├── Jobs/                      # Queued jobs
│   ├── Livewire/                  # Livewire components (primary UI layer)
│   │   ├── Actions/               # Shared Livewire actions
│   │   ├── Attendance/            # Attendance features
│   │   ├── Auth/                  # Auth-related components
│   │   ├── Branches/              # Branch management
│   │   ├── [Feature]/             # Feature-organized components (Members, Donations, etc.)
│   │   ├── Concerns/              # Component traits (HasFilterableQuery, HasQuotaComputed)
│   │   ├── Navigation/            # Navigation components
│   │   ├── Settings/              # Settings pages
│   │   ├── SuperAdmin/            # Super admin only pages
│   │   ├── Dashboard.php          # Main dashboard
│   │   └── Upgrade/               # Plan upgrade flow
│   ├── Mail/                      # Mail classes
│   ├── Models/
│   │   ├── [CentralModels]        # Tenant, Domain, SuperAdmin, User, etc.
│   │   └── Tenant/                # Tenant-scoped models (Member, Visitor, etc.)
│   ├── Notifications/             # Notification classes
│   ├── Observers/                 # Model event listeners
│   ├── Policies/                  # Authorization policies per model
│   │   └── Concerns/              # Shared authorization logic
│   ├── Providers/                 # Service providers
│   └── Services/                  # Domain business logic
├── bootstrap/                     # Framework bootstrap
│   ├── app.php                    # Application configuration (middleware, exceptions)
│   └── providers.php              # Service provider registration
├── config/                        # Configuration files
├── database/
│   ├── migrations/                # Database migrations
│   │   ├── landlord/              # Central database migrations
│   │   └── tenant/                # Tenant database migrations
│   ├── factories/                 # Model factories for testing
│   ├── seeders/                   # Database seeders
│   └── database.sqlite            # SQLite central database
├── resources/
│   ├── views/
│   │   ├── components/
│   │   │   ├── layouts/           # Main layout wrappers (app, auth, guest, onboarding)
│   │   │   │   ├── app.blade.php  # Main authenticated app layout
│   │   │   │   ├── auth.blade.php # Auth pages layout
│   │   │   │   └── superadmin/    # Super admin layout
│   │   │   ├── app/               # App-specific components (navigation, sidebar)
│   │   │   └── [other]/           # Reusable UI components
│   │   ├── livewire/              # Livewire component views
│   │   │   ├── members/
│   │   │   ├── attendance/
│   │   │   └── [feature]/         # Organized by feature
│   │   ├── emails/                # Email templates
│   │   ├── pdf/                   # PDF templates
│   │   ├── receipts/              # Receipt templates
│   │   ├── flux/                  # Flux UI custom components
│   │   └── welcome.blade.php      # Landing page
│   ├── css/                       # Stylesheets
│   └── js/                        # Client-side JavaScript
├── routes/
│   ├── web.php                    # Central/public routes
│   ├── tenant.php                 # Tenant-specific routes (protected by tenancy middleware)
│   ├── superadmin.php             # Super admin routes (protected by superadmin guard)
│   └── console.php                # Artisan commands
├── storage/
│   ├── app/
│   │   └── tenants/               # Per-tenant storage directories
│   ├── logs/
│   └── framework/
├── tests/
│   ├── Feature/                   # Feature tests (integration)
│   │   ├── [Feature]/             # Organized by feature domain
│   │   ├── DashboardTest.php
│   │   └── ExampleTest.php
│   ├── Unit/                      # Unit tests
│   │   ├── Models/
│   │   └── Services/
│   └── TestCase.php               # Base test class
├── .planning/
│   └── codebase/                  # Generated codebase analysis documents
├── public/                        # Web-accessible assets
├── artisan                        # Artisan CLI entry point
├── composer.json                  # PHP dependencies
├── composer.lock
├── package.json                   # Node dependencies (Tailwind, Vite, etc.)
├── package-lock.json
├── CLAUDE.md                      # Project-specific guidelines
└── README.md
```

## Directory Purposes

**`app/`:**
- Purpose: All application business logic and domain code
- Contains: Models, controllers, services, Livewire components, policies, jobs
- Key files: `app/Models/Tenant.php` (central tenant model), `app/Providers/AppServiceProvider.php` (service registration)

**`app/Livewire/`:**
- Purpose: Primary UI layer - reactive components rendering with server-side logic
- Contains: Feature-organized components in subdirectories (Members/, Attendance/, Donations/)
- Pattern: Each feature subdirectory contains related Livewire classes and their views in `resources/views/livewire/`
- Key files: `Dashboard.php` (main dashboard component), various feature Index/Show/Create/Edit components

**`app/Models/`:**
- Purpose: Eloquent model definitions
- Central models: Located in root (Tenant.php, SuperAdmin.php, User.php, SubscriptionPlan.php, etc.)
- Tenant models: Located in `Tenant/` subdirectory (Member.php, Visitor.php, Donation.php, etc.)
- Pattern: Models include relationships, observers, factories; no business logic

**`app/Services/`:**
- Purpose: Encapsulated business logic, external API integration, complex operations
- Examples: `PlanAccessService` (quota/access checks), `BranchContextService` (session state), `TextTangoService` (SMS)
- Pattern: Scoped or singleton services; dependency injected into components/controllers

**`app/Http/Middleware/`:**
- Purpose: Request filtering and cross-cutting concerns
- Examples: `SuperAdminAuthenticate`, `EnsureOnboardingComplete`, `EnsureModuleEnabled`, `EnforceQuota`
- Pattern: Single responsibility per middleware; registered in `bootstrap/app.php`

**`app/Policies/`:**
- Purpose: Authorization rules per model/resource
- Pattern: Policy method per action (create, view, update, delete)
- Example: `MemberPolicy::create()` checks branch access + member quota
- Usage: `authorize()` in components or `@can()` in Blade

**`app/Jobs/`:**
- Purpose: Asynchronous work queued for later execution
- Examples: `SendBulkSmsJob`, `SendAnnouncementJob`, `ProcessAnnouncementJob`
- Pattern: Implement ShouldQueue; dispatch from Livewire components or event listeners

**`app/Enums/`:**
- Purpose: Type-safe enumerated values
- Examples: `MembershipStatus`, `Gender`, `BillingCycle`, `PlanModule`, `QuotaType`
- Pattern: Backed enums with case methods and labels; used in model casts

**`routes/`:**
- `web.php`: Central routes (home, onboarding, settings, webhooks)
- `tenant.php`: Tenant-scoped routes - protected by InitializeTenancyByDomain middleware
- `superadmin.php`: Super admin only routes - protected by SuperAdminAuthenticate middleware
- `console.php`: Artisan command definitions

**`database/migrations/`:**
- Organized into `landlord/` (central DB) and `tenant/` (each tenant's DB)
- Convention: Timestamp + descriptive name (e.g., `2025_12_30_231859_create_branches_table.php`)
- Tenant migrations auto-run on tenant creation via TenancyServiceProvider

**`resources/views/`:**
- `components/layouts/`: Layout wrappers (app, auth, guest, onboarding, superadmin)
- `livewire/`: Blade templates for Livewire components (mirrored in directory structure)
- `emails/`: Mailable templates
- `pdf/`: PDF generation templates
- `flux/`: Custom Flux UI component wrappers

**`tests/`:**
- `Feature/`: Feature tests using Pest; organized by feature domain (Members/, Attendance/, etc.)
- `Unit/`: Unit tests for models and services
- Convention: Test file names end with `Test.php`; test methods start with `test`

## Key File Locations

**Entry Points:**
- `routes/web.php`: Central/public entry point
- `routes/tenant.php`: Tenant application entry point (with tenancy middleware)
- `routes/superadmin.php`: Super admin entry point (admin.* domain)
- `resources/views/components/layouts/app.blade.php`: Main authenticated layout
- `app/Livewire/Dashboard.php`: Main application dashboard

**Configuration:**
- `bootstrap/app.php`: Middleware registration, exception handling
- `bootstrap/providers.php`: Service provider registration
- `config/app.php`: Application basics (name, timezone, locale)
- `config/tenancy.php`: Multi-tenancy configuration
- `config/fortify.php`: Authentication features
- `config/mail.php`: Email configuration

**Core Logic:**
- `app/Services/PlanAccessService.php`: All quota/access control
- `app/Services/BranchContextService.php`: Session branch context
- `app/Services/TenantCreationService.php`: Tenant onboarding
- `app/Providers/AppServiceProvider.php`: Policy registration, blade directives
- `app/Providers/TenancyServiceProvider.php`: Tenancy event handling

**Testing:**
- `tests/TestCase.php`: Base test class with tenant setup helpers
- `tests/Feature/DashboardTest.php`: Example feature test structure
- `database/factories/Tenant/MemberFactory.php`: Example factory with states

## Naming Conventions

**Files:**
- Controllers: `[Feature]Controller.php` (e.g., `MemberController.php`)
- Livewire components: `[Feature][Action].php` (e.g., `MemberIndex.php`, `MemberShow.php`)
- Models: `[Singular]` (e.g., `Member.php`, `Visitor.php`)
- Policies: `[Model]Policy.php` (e.g., `MemberPolicy.php`)
- Services: `[Domain]Service.php` (e.g., `PlanAccessService.php`)
- Jobs: `[Action][Noun]Job.php` (e.g., `SendBulkSmsJob.php`)
- Tests: `[Feature]Test.php` (e.g., `MemberIndexTest.php`)
- Migrations: `YYYY_MM_DD_HHmmss_[description].php`
- Enums: `[PluralConcept]` or `[SingleConcept]` (e.g., `MembershipStatus.php`, `Gender.php`)
- Trait/Concern: `[Capability]` or `Has[Behavior]` (e.g., `HasFilterableQuery.php`, `HasQuotaComputed.php`)

**Directories:**
- Plurals for collections: `app/Models/`, `app/Services/`, `app/Jobs/`, `app/Policies/`
- Feature-based: `app/Livewire/Members/`, `app/Livewire/Attendance/`
- Singular for domain concepts: `app/Http/Middleware/`, `app/Models/Tenant/`

**Functions/Methods:**
- Livewire actions: `handle[Action]()` or simple verb (e.g., `saveMember()`, `deleteMember()`)
- Computed properties: Simple noun or property-like names (e.g., `currentBranch`, `totalActiveMembers`)
- Query filters: `apply[Filter]()` (e.g., `applySearch()`, `applyEnumFilter()`)
- Service methods: Verb + noun (e.g., `getMemberQuota()`, `canCreateMember()`)

**Variables:**
- camelCase for all variables and properties
- Booleans: `is[Adjective]` or `has[Noun]` (e.g., `isActive`, `hasAccess`)
- Collections: Plural nouns (e.g., `$members`, `$visitorFollowUps`)
- IDs: `[Model]Id` (e.g., `$memberId`, `$branchId`)

**Classes:**
- TitleCase for all classes
- Traits: Prefixed with `Has` or `Concerns` namespace (e.g., `HasFilterableQuery`)

## Where to Add New Code

**New Feature (e.g., Members):**
- Primary code:
  - Model: `app/Models/Tenant/Member.php`
  - Livewire component: `app/Livewire/Members/MemberIndex.php`, `MemberShow.php`, etc.
  - Policy: `app/Policies/MemberPolicy.php`
- Routes: Add to `routes/tenant.php` or included via route group
- Views: `resources/views/livewire/members/member-*.blade.php`
- Tests: `tests/Feature/Members/MemberIndexTest.php`, etc.
- Services (if complex): `app/Services/MemberManagementService.php`

**New Livewire Component:**
- Create in `app/Livewire/[Feature]/[ComponentName].php`
- Create view at `resources/views/livewire/[feature]/[component-name].blade.php`
- Use `#[Layout('components.layouts.app')]` attribute for page components
- Register with route in `routes/tenant.php` if it's a page route
- Import from `App\Livewire\[Feature]\[ComponentName]` in route definition

**New Service:**
- Create in `app/Services/[Domain]Service.php`
- Register in `AppServiceProvider::register()` if it's a singleton or scoped
- Type-hint in component/controller constructors
- Implement single responsibility

**New Model:**
- Run `php artisan make:model [Name] --migration --factory` for tenant models
- Place in `app/Models/Tenant/` for tenant-scoped, `app/Models/` for central
- Define relationships with return type hints
- Add to factory with meaningful states
- Create tests in `tests/Feature/` for feature context

**New Policy:**
- Create in `app/Policies/[Model]Policy.php`
- Define methods: `create()`, `view()`, `update()`, `delete()`, etc.
- Register in `AppServiceProvider::boot()` via `Gate::policy()`
- Use `authorize()` in Livewire components before performing action

**New Job:**
- Run `php artisan make:job [Name]`
- Implement `ShouldQueue` interface
- Define `handle()` method
- Dispatch from Livewire: `dispatch(new [Job]())`
- Configure retry logic if needed

**New Middleware:**
- Create in `app/Http/Middleware/[Name].php`
- Register in `bootstrap/app.php` via `$middleware->alias()` or middleware groups
- Use `Closure $next` pattern with typed request/response

**New Query Scope/Relationship:**
- Add as method on Model class
- Return relation type or Builder for scopes
- Use in queries: `Model::withRelation()->where(...)`

## Special Directories

**`storage/tenants/`:**
- Purpose: Per-tenant file storage and databases
- Generated: Yes (created per tenant on setup)
- Committed: No (.gitignored)
- Structure: `storage/tenants/{tenant_id}/` with subdirectories for uploads, databases

**`database/migrations/tenant/`:**
- Purpose: Tenant-specific schema changes
- Generated: No (written by developers)
- Committed: Yes
- Auto-run: Yes, when new tenant is created via TenancyServiceProvider

**`.planning/codebase/`:**
- Purpose: Generated codebase analysis documents
- Generated: Yes (by mapping tool)
- Committed: Yes
- Usage: Referenced by `/gsd:plan-phase` and `/gsd:execute-phase` commands

**`node_modules/` & `vendor/`:**
- Purpose: Dependency packages
- Generated: Yes (from composer.json and package.json)
- Committed: No (.gitignored)

**`storage/logs/`:**
- Purpose: Application error logs
- Generated: Yes (at runtime)
- Committed: No
- Rotation: Handled by Laravel logging config

---

*Structure analysis: 2026-02-03*
