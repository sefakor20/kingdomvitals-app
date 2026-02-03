# Architecture

**Analysis Date:** 2026-02-03

## Pattern Overview

**Overall:** Multi-Tenant SaaS with Domain-Based Isolation & Reactive Livewire UI

**Key Characteristics:**
- Multi-tenant architecture using Stancl Tenancy with separate databases per tenant
- Domain-based tenant identification (each tenant has dedicated subdomain/custom domain)
- Dual-layer authentication: Super Admin (central) and Tenant Users (tenant database)
- Reactive server-side rendering using Livewire 4 with Flux UI components
- Plan-based access control with quota enforcement
- Event-driven tenancy lifecycle management

## Layers

**Presentation Layer (Frontend):**
- Purpose: Reactive UI built with Livewire 4 components and Flux UI library
- Location: `app/Livewire/` + `resources/views/livewire/` + `resources/views/components/`
- Contains: Feature-organized Livewire components (Members, Attendance, Donations, etc.), Blade templates, Flux UI component wrappers
- Depends on: Livewire traits (HasFilterableQuery, HasQuotaComputed), Services (PlanAccessService, BranchContextService)
- Used by: End users, Super Admin, Tenant admins

**Business Logic Layer (Service & Action Layer):**
- Purpose: Encapsulate domain logic, validations, and orchestration
- Location: `app/Services/` (PlanAccessService, BranchContextService, TenantCreationService, etc.), `app/Actions/` (Fortify actions)
- Contains: Domain-specific services for billing, SMS, reporting, image processing; Fortify authentication actions
- Depends on: Models, external APIs (Paystack, TextTango), configuration
- Used by: Controllers, Livewire components, Jobs, Console commands

**Data Access Layer (Models & Queries):**
- Purpose: Define domain entities and relationships
- Location: `app/Models/` (central/landlord models), `app/Models/Tenant/` (tenant-scoped models)
- Contains: Eloquent models with relationships, factories, computed properties, life-cycle hooks
- Depends on: Database schema, Observers, Policies
- Used by: Services, Livewire components, Controllers

**HTTP & Routing Layer:**
- Purpose: Handle HTTP requests and route dispatch
- Location: `routes/web.php` (central), `routes/tenant.php` (tenant), `routes/superadmin.php` (super admin)
- Contains: Route definitions, middleware grouping, webhook handlers
- Depends on: Controllers, Livewire components, Middleware
- Used by: HTTP clients, Webhooks, Form submissions

**Middleware Layer:**
- Purpose: Cross-cutting concerns: authentication, authorization, tenancy initialization
- Location: `app/Http/Middleware/`
- Contains: SuperAdminAuthenticate, EnsureOnboardingComplete, EnsureModuleEnabled, EnforceQuota
- Depends on: Auth facades, Config
- Used by: Route groups

**Policy & Authorization Layer:**
- Purpose: Define authorization rules per model/resource
- Location: `app/Policies/` (with `Concerns/ChecksPlanAccess.php` for plan-based access)
- Contains: Model policies (MemberPolicy, DonationPolicy, etc.), report access control
- Depends on: Models, PlanAccessService
- Used by: Livewire components, Controllers via `authorize()` method

**Database Layer:**
- Purpose: Schema definitions, migrations, seeding
- Location: `database/migrations/` (split: landlord & tenant), `database/factories/`, `database/seeders/`
- Contains: Migration files for central and tenant databases, model factories
- Depends on: Eloquent models
- Used by: Migrations, Artisan commands, Tests

**Job & Queue Layer:**
- Purpose: Asynchronous job processing
- Location: `app/Jobs/`
- Contains: SendBulkSmsJob, SendAnnouncementJob, CreateTenantStorageDirectories, etc.
- Depends on: Services, Models, configuration
- Used by: Livewire components, Controllers, Event listeners

## Data Flow

**Tenant Signup & Onboarding:**

1. User lands on super admin domain (admin.kingdomvitals.com)
2. Super Admin creates tenant via SuperAdmin\Tenants\TenantCreate
3. TenantCreationService creates database, runs migrations, creates storage directories
4. Tenant domain created and linked
5. Tenant user attempts to access tenant domain
6. InitializeTenancyByDomain middleware identifies tenant from domain
7. Tenant user completes onboarding flow (OnboardingWizard Livewire component)
8. OnboardingService validates organization setup, configures branches
9. User is redirected to dashboard

**User Interaction Flow (Reactive Updates):**

1. User interacts with Livewire component (click, form input)
2. Livewire dispatches event or updates property (wire:click, wire:model)
3. Component processes in browser, minimal payload sent to server
4. Server executes component method with updated state
5. Authorization checked via policies (if action requires it)
6. Business logic executed via Service or Action
7. Database updated via Eloquent models
8. Computed properties recalculated (#[Computed] attribute)
9. Updated HTML returned to browser
10. Livewire re-renders only changed DOM elements

**Report & Export Flow:**

1. User selects filters in Report component
2. HasReportFilters trait applies filters to query
3. HasReportExport trait generates file (CSV via ReportExportService)
4. File returned to user for download

**Multi-Tenant Isolation:**

1. Request comes in with domain header
2. InitializeTenancyByDomain middleware resolves tenant from domain
3. Stancl Tenancy sets connection to tenant database
4. All Model queries automatically scoped to tenant database
5. Session context set via BranchContextService for per-branch operations
6. When request ends, tenancy reverted to central context

**Subscription Plan Enforcement:**

1. User attempts action (create member, send SMS, upload file)
2. PlanAccessService checks tenant plan
3. If quota exceeded, EnforceQuota middleware returns 403 or shows upgrade prompt
4. If under quota, action proceeds
5. Action usage tracked in TenantUsageSnapshot
6. Dashboard displays quota warnings if approaching limits

**Super Admin Management Flow:**

1. Super Admin accesses admin domain (separate route group)
2. SuperAdminAuthenticate middleware validates super admin status
3. No tenancy middleware applied (stays in central database)
4. Super Admin can view/manage tenants, plans, revenue, announcements
5. Super Admin can impersonate tenant via ImpersonationController
6. ImpersonationLog created for audit trail

**Webhook Processing:**

1. External service (Paystack, TextTango) sends webhook
2. Webhook route bypasses CSRF (configured in bootstrap/app.php)
3. Controller parses payload, verifies signature
4. Job dispatched for async processing (if needed)
5. Response returned immediately to webhook sender

**State Management:**

- Livewire component properties: Local state for forms, filters, UI toggling
- Session via BranchContextService: Current branch context across page navigation
- Database: Persistent application state for models
- Config & SystemSettings: Global application configuration
- Tenancy context: Which tenant database is active (set by middleware)

## Key Abstractions

**Livewire Component Concerns (Traits):**
- Location: `app/Livewire/Concerns/`
- `HasFilterableQuery`: Generic filtering logic (search, enum, boolean, date range)
- `HasQuotaComputed`: Computed quota properties from PlanAccessService
- `HasReportExport`: Common report export patterns
- `HasReportFilters`: Report-specific filtering
- Pattern: Traits extract reusable behavior; components compose them

**Services (Domain Logic):**
- `PlanAccessService`: Centralized plan access checks, quota computation, feature flags
- `BranchContextService`: Session-based branch context management
- `TenantCreationService`: Complex tenant setup orchestration
- `TenantUpgradeService`: Plan upgrade logic and prorating
- `TextTangoService`: SMS sending via TextTango API
- `PaystackService`: Payment processing integration
- Pattern: Single responsibility; scoped/singleton registration in container

**Models:**
- Central: `Tenant`, `Domain`, `SuperAdmin`, `User`, `Announcement`, `SubscriptionPlan`, `PlatformInvoice`, `SystemSetting`
- Tenant-scoped: `Member`, `Visitor`, `Attendance`, `Donation`, `Branch`, `Cluster`, `Equipment`, etc.
- Pattern: Models with relationships, observers for side effects, factories for testing

**Enums:**
- Location: `app/Enums/`
- Examples: `MembershipStatus`, `Gender`, `BillingCycle`, `PlanModule`, `QuotaType`
- Pattern: Replaces string/int columns; provides type safety and case methods

**Jobs:**
- `SendBulkSmsJob`: Queued SMS sending
- `ProcessAnnouncementJob`: Announcement lifecycle
- `SendAnnouncementJob`: Broadcast announcements
- Pattern: Implement ShouldQueue; dispatch from Livewire components or events

**Observers:**
- `MemberObserver`: Tracks activity on member changes
- `SmsLogObserver`: Tracks SMS delivery status
- `TenantObserver`: Handles tenant lifecycle events
- Pattern: Listen to model events; trigger side effects (logging, notifications)

**Policies:**
- Pattern: Authorization rules per model
- Example: `MemberPolicy::create()` checks branch access and quota
- `Concerns/ChecksPlanAccess`: Shared trait for checking plan module access
- Used with `authorize()` in components or `can()` in templates

## Entry Points

**Web Routes:**
- Location: `routes/web.php`
- Route: `/` (home - redirects based on domain)
- Route: `/dashboard` (main app dashboard)
- Route: `/onboarding` (setup wizard)
- Route: `/settings/*` (user profile, password, appearance, 2FA)
- Middleware: auth, verified, onboarding.complete

**Tenant Routes:**
- Location: `routes/tenant.php`
- Protected by: InitializeTenancyByDomain middleware
- Contains: All tenant-scoped operations (members, attendance, donations, etc.)
- Public routes: `/checkin/{token}` (mobile self check-in), `/branches/{branch}/give` (public giving)
- Webhook routes: `/webhooks/paystack` (no auth)

**Super Admin Routes:**
- Location: `routes/superadmin.php`
- Protected by: SuperAdminAuthenticate middleware
- Domain: admin.kingdomvitals.com or admin.* subdomain
- Contains: Tenant management, billing, analytics, system settings

**Livewire Component Entry Points (Pages):**
- `Dashboard::class`: Main dashboard with metrics
- `MemberIndex::class`: Member list and CRUD
- `VisitorIndex::class`: Visitor list with follow-up tracking
- `AttendanceIndex::class`: Attendance recording and analytics
- All organized in `app/Livewire/` with feature subdirectories

**Controllers (Minimal Use):**
- `ImpersonationController`: Super admin tenant impersonation
- `InvoiceController`: Invoice PDF generation
- Webhook Controllers: `PaystackWebhookController`, `TextTangoWebhookController`
- Fortify Auth Controllers: Custom super admin authentication

**CLI Entry Points:**
- Location: `app/Console/Commands/` (auto-registered in Laravel 12)
- Routes defined in: `routes/console.php`
- Example commands: custom Artisan commands for batch operations

## Error Handling

**Strategy:** Centralized exception handling with user-friendly fallbacks

**Patterns:**
- Livewire components dispatch `#[On]` event listeners to handle errors
- Services throw `\Exception` with descriptive messages
- Policies throw `AuthorizationException` for authorization failures
- Middleware returns 403 responses for quota/access violations
- Observers catch errors silently (log but don't break flow)
- Controllers catch exceptions and return appropriate HTTP responses
- Jobs use `maxExceptions()` and `retryUntil()` for queue handling

## Cross-Cutting Concerns

**Logging:**
- ActivityEvent enum tracks member changes via MemberObserver
- SmsLog model tracks all SMS (created via SmsLogObserver)
- SuperAdminActivityLog tracks super admin actions
- TenantImpersonationLog tracks impersonation for audit
- Exceptions logged via Laravel's exception handler

**Validation:**
- Form Request classes in `app/Http/Requests/` (not heavily used with Livewire)
- Livewire components inline validation: `$this->validate(['field' => 'rules'])`
- Model factory states provide valid test data
- PasswordValidationRules in Fortify actions

**Authentication:**
- Central: Super Admin guard ('superadmin') + 2FA support
- Tenant: Fortify-provided authentication with email verification
- Both: 2FA via TOTP (QR codes, recovery codes)
- Session-based; cookies store session ID
- Fortify customizations in `FortifyServiceProvider`

**Authorization:**
- Models: Policies registered in `AppServiceProvider` via `Gate::policy()`
- Views/Components: `@can()`, `@feature()`, `@module()` Blade directives
- Plan-based: `PlanAccessService::hasModule()`, `hasFeature()`, `canCreate()`
- Quota-based: `EnforceQuota` middleware + `canCreateWithinQuotaFor()` method

**Tenancy & Multi-Database:**
- Stancl Tenancy handles tenant identification by domain
- Separate SQLite database per tenant in `storage/tenants/`
- Central database for landlord data (tenants, plans, super admins)
- Middleware ensures correct connection before query execution
- TenancyServiceProvider registers tenancy events and listeners

---

*Architecture analysis: 2026-02-03*
