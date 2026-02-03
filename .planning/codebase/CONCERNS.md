# Codebase Concerns

**Analysis Date:** 2026-02-03

## Large Livewire Components

**Monolithic Component Classes:**
- Problem: Several Livewire components exceed 800+ lines, making them difficult to maintain and test
- Files:
  - `app/Livewire/Visitors/VisitorIndex.php` (875 lines)
  - `app/Livewire/Members/MemberIndex.php` (733 lines)
  - `app/Livewire/Attendance/AttendanceAnalytics.php` (661 lines)
  - `app/Livewire/Equipment/EquipmentIndex.php` (649 lines)
  - `app/Livewire/Children/ChildrenDirectory.php` (621 lines)
  - `app/Livewire/Finance/DonorEngagement.php` (611 lines)
- Impact: Difficult to test individual behaviors, high cognitive load, increased risk of bugs during modifications
- Fix approach: Extract shared concerns into traits (filters, bulk actions, modals). Decompose into smaller components or use view models. Leverage Livewire 4 component composition patterns.

## Potential N+1 Query Problems

**Missing Eager Loading:**
- Problem: Multiple locations use `.get()` to fetch collections without eager loading relationships, which can trigger N+1 queries when relationships are accessed in loops
- Files: Multiple files including:
  - `app/Livewire/Finance/DonorEngagement.php` (lines 264, 328, 423, 502, 523, 538, 558, 588)
  - `app/Livewire/Reports/Membership/MemberDemographics.php` (lines 45, 140)
  - `app/Livewire/Attendance/MonthlyAttendanceComparison.php` (lines 60, 101)
  - `app/Livewire/Services/ServiceShow.php` (lines 108, 119, 130)
  - `app/Livewire/Equipment/EquipmentShow.php` (lines 154, 163, 172)
- Impact: Severe performance degradation with large datasets; query counts grow linearly with record count
- Fix approach: Add `.with()` eager loading for all accessed relationships. Create computed properties that properly manage query optimization. Review database queries in all Livewire components used in reporting/analytics.

## Webhook Security - Multi-Tenant SMS Handling

**Inefficient and Risky Webhook Processing:**
- Problem: `TextTangoWebhookController::findSmsLogAcrossTenants()` iterates through ALL tenants in a loop to find a single SMS log record (lines 73-112 in `app/Http/Controllers/Webhooks/TextTangoWebhookController.php`)
- Files: `app/Http/Controllers/Webhooks/TextTangoWebhookController.php`
- Impact:
  - Performance degrades linearly with tenant count
  - Multiple unnecessary tenancy context switches
  - Risk of processing messages for wrong tenant if identifiers are ambiguous
- Fix approach: Store provider message IDs in a centralized lookup table (platform database) instead of searching through all tenants. Use platform-level SMS tracking to correlate webhook messages before initializing tenant context.

## Lack of Input Validation on File Uploads

**Insufficient File Upload Validation:**
- Problem: While `ImageProcessingService::validateLogo()` and `processMemberPhoto()` validate file types, there's no content-based validation (magic bytes check) to prevent malicious files masquerading as images
- Files: `app/Services/ImageProcessingService.php`
- Impact: Potential for file-based attacks; relies only on MIME type which can be spoofed
- Fix approach: Add magic byte validation using FINFO_MAGIC_MIME_TYPE or similar. Consider scanning uploaded images for embedded code.

## Storage Quota Enforcement Gaps

**Storage Quota Check Placed After Upload Decision:**
- Problem: `MemberIndex` and `MemberShow` check storage quota but may still process uploads if quota checking isn't enforced consistently
- Files:
  - `app/Livewire/Members/MemberIndex.php` (lines 500, 566)
  - `app/Livewire/Members/MemberShow.php` (line 322)
- Impact: Potential to exceed storage quotas; inconsistent quota enforcement across components
- Fix approach: Create centralized storage validation trait. Enforce at middleware or service layer before file processing begins.

## Complex Image Processing Without Error Recovery

**Missing Graceful Degradation in Image Processing:**
- Problem: `ImageProcessingService::processLogo()` creates multiple resized versions in a loop (lines 40-58). If processing fails mid-way through creating sizes, orphaned files may remain
- Files: `app/Services/ImageProcessingService.php`
- Impact: Storage leaks; incomplete logo variants serve to users
- Fix approach: Implement transaction-like cleanup: collect all processed files, validate completion, then commit. Use temporary directory staging before moving to final location.

## Insufficient Test Coverage for Critical Paths

**Untested Critical Flows:**
- Problem: While 115 test files exist, several critical business logic areas lack coverage
- Files:
  - Payment processing in `PublicGivingForm` - no comprehensive error scenario tests
  - Subscription plan enforcement across operations - insufficient edge case coverage
  - Multi-tenant data isolation in webhook handlers - no isolation verification tests
  - Quota enforcement - limited test coverage for boundary conditions
- Impact: Undetected regressions in financial workflows; potential data leaks between tenants
- Fix approach: Add integration tests for quota enforcement at various limits. Add tests verifying tenant data isolation in webhook handlers. Test payment error recovery paths.

## Tenancy Context Management Complexity

**Manual Tenancy Initialization/Termination:**
- Problem: Webhook handlers and scheduled jobs manually call `tenancy()->initialize()` and `tenancy()->end()` (e.g., `TextTangoWebhookController` lines 51, 64, 81, 102, 107). Missing cleanup or exceptions leave context uncleaned
- Files:
  - `app/Http/Controllers/Webhooks/TextTangoWebhookController.php`
  - Any scheduled jobs that initialize tenancy
- Impact: State leakage between requests; subsequent operations may run in wrong tenant context
- Fix approach: Wrap in try-finally blocks consistently. Consider custom middleware to handle context lifecycle. Use Spatie tenancy's `tenancy()->execute()` callback pattern where available.

## Security: Impersonation Feature Lacks Audit Trail

**Minimal Logging on Impersonation Actions:**
- Problem: `ImpersonationController` (route in `routes/tenant.php`) allows super admins to impersonate users but may lack comprehensive audit logging
- Files: `app/Http/Controllers/ImpersonationController.php`
- Impact: Unauthorized access difficult to detect and investigate
- Fix approach: Add detailed audit logs for all impersonation enter/exit events including IP, timestamp, duration, and actions taken while impersonated.

## Authorization Checks Not Consistently Applied

**Missing Authorization in Some Livewire Methods:**
- Problem: While most Livewire components use `$this->authorize()`, some internal methods lack authorization checks before sensitive operations
- Files: Multiple Livewire components
- Impact: Potential authorization bypass if private methods are called through component manipulation
- Fix approach: Add authorization checks to all public methods. Mark private methods as truly private. Audit all Livewire action methods systematically.

## Configuration Exposure Risk

**Debug Flag in Public Configuration:**
- Problem: `config/app.php` has `'debug' => (bool) env('APP_DEBUG', false)` which may expose sensitive information in production if misconfigured
- Files: `config/app.php` (line 42)
- Impact: Stack traces, environment variables, and sensitive data exposed to unauthenticated users in production
- Fix approach: Ensure APP_DEBUG=false in production. Add deploy-time validation. Consider removing debug=true from ANY non-local environment configs.

## Payment Processing Error Handling

**Generic Exception Handling in Billing Service:**
- Problem: `PlatformBillingService::generateMonthlyInvoices()` catches broad `\Exception` (line 59), logs error, but continues. Failed invoice generation may silently fail without alerting administrators
- Files: `app/Services/PlatformBillingService.php`
- Impact: Silent billing failures; tenants not invoiced; revenue loss not detected
- Fix approach: Separate recoverable exceptions from fatal ones. Send admin alerts for failed invoice generation. Implement retry logic with exponential backoff for transient failures.

## SMS Template Default Seeder Data

**Hard-coded SMS Template Seeding:**
- Problem: Default SMS templates are seeded via migration `2026_01_03_000000_seed_default_sms_templates.php`, but modification requires new migration if templates need updating
- Files: `database/migrations/tenant/2026_01_03_000000_seed_default_sms_templates.php`
- Impact: SMS template updates require database migrations; difficult to hotfix message content
- Fix approach: Move configurable SMS templates to config file or admin UI. Reserve migrations for schema only. Allow templates to be managed through application without migrations.

## Public Route Access Control

**Public Self-Check-In and Giving Routes Require Token Validation:**
- Problem: Routes like `/checkin/{token}` and `/branches/{branch}/give` are public but rely on token/branch ID validation only. No rate limiting visible
- Files: `routes/tenant.php`
- Impact: Potential for abuse (SMS spam, forced donations); sensitive check-in data exposed
- Fix approach: Add rate limiting middleware to public routes. Implement token expiration validation. Add abuse monitoring/alerting.

## Missing Deprecation Warnings for Removed Features

**No Deprecation Timeline for Registration Disabling:**
- Problem: Feature `Features::registration()` was disabled in `config/fortify.php` per recent commit ("disable user registration feature for invitation-only access"). Users expecting registration receive no deprecation notice or migration path
- Files: Config files and Fortify configuration
- Impact: Broken user workflows; external documentation references outdated flows
- Fix approach: Add feature flag with deprecation warnings. Provide migration guide to affected users. Update documentation links.

## Database Migrations Without Strict Ordering

**Potential Migration Execution Order Issues:**
- Problem: Migrations use timestamps, but complex dependency chains (e.g., `add_age_group_id_to_members_table` depends on `create_age_groups_table`) may fail if executed out of order or partially
- Files: `database/migrations/tenant/` - multiple interdependent migrations
- Impact: Failed deployments; database inconsistencies during rollback
- Fix approach: Add explicit foreign key dependency validation in migrations. Use `->nullable()->constrained()` patterns to ensure referential integrity. Document migration dependency chains.

## Inconsistent Error Responses in Public Routes

**Mixed Response Formats for Public API Routes:**
- Problem: Public routes (giving form, check-in) may return different error formats depending on request type (JSON vs HTML)
- Files:
  - `app/Livewire/Giving/PublicGivingForm.php`
  - `app/Livewire/Attendance/MobileSelfCheckIn.php`
- Impact: Client code must handle multiple error response types; poor developer experience
- Fix approach: Standardize error response format using Laravel's exception handling. Return consistent JSON structure for AJAX requests.

## Performance: Image Processing Blocks Request Cycle

**Synchronous Image Processing:**
- Problem: `ImageProcessingService::processLogo()` creates 5 resized logo variants synchronously during request, blocking response until all processing completes
- Files: `app/Services/ImageProcessingService.php` (used in settings components)
- Impact: Slow upload responses; poor user experience with large images
- Fix approach: Queue image processing to job queue. Return immediate success response. Notify user when processing completes via websocket or background job notification.

## Duplicate Tenancy End Calls

**Redundant Tenancy Cleanup:**
- Problem: `TextTangoWebhookController::findSmsLogAcrossTenants()` calls `tenancy()->end()` inside loop (line 102) AND in finally block (line 107), creating duplicate end calls
- Files: `app/Http/Controllers/Webhooks/TextTangoWebhookController.php` (lines 102-107)
- Impact: Potential state issues; inefficient cleanup
- Fix approach: Remove redundant `tenancy()->end()` call at line 102. Let finally block handle cleanup consistently.

---

*Concerns audit: 2026-02-03*
