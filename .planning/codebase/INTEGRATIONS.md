# External Integrations

**Analysis Date:** 2026-02-03

## APIs & External Services

**SMS & Messaging:**
- TextTango - SMS delivery platform
  - SDK/Client: Direct HTTP via `Illuminate\Support\Facades\Http`
  - Service: `App\Services\TextTangoService` (located at `app/Services/TextTangoService.php`)
  - Auth: `TEXTTANGO_API_KEY`, `TEXTTANGO_SENDER_ID`
  - Base URL: `TEXTTANGO_BASE_URL` (default: https://app.texttango.com/api/v1)
  - Features:
    - Bulk SMS sending via `/sms/campaign/send` endpoint
    - Campaign tracking via `/sms/campaign/track/{trackingId}`
    - Single message tracking via `/sms/campaign/track/single/{messageId}`
    - Account balance checking via `/account/me/balance`
  - Multi-tenant support: Each branch can have separate SMS credentials stored encrypted in branch settings
  - Webhook endpoint: `POST /webhooks/texttango` (handled by `App\Http\Controllers\Webhooks\TextTangoWebhookController`)
  - Webhook secret: `TEXTTANGO_WEBHOOK_SECRET` for request validation

**Email Services:**
- Multiple providers configured (optional):
  - Postmark - Premium email delivery (key: `POSTMARK_API_KEY`)
  - AWS SES - Simple Email Service (keys: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`)
  - Resend - Email API (key: `RESEND_API_KEY`)
  - SMTP - Custom SMTP server (default for local development)
  - Log - Development logging (default in .env.example)
- Default mailer: Configured via `MAIL_MAILER` env var (default: log)
- Mail configuration: `config/mail.php`
- Queued mails: `AnnouncementMail`, `DonationReceiptMail`, `PlatformInvoiceMail`, `PlatformPaymentReceivedMail`

## Data Storage

**Databases:**
- Primary: SQLite (development default)
  - Connection: `DB_CONNECTION=sqlite`
  - Path: `database/database.sqlite`
  - Client: Laravel Eloquent ORM with built-in PDO driver
- Production options:
  - MySQL/MariaDB
    - Connection: `DB_CONNECTION=mysql`
    - Config keys: `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`
  - PostgreSQL
    - Connection: `DB_CONNECTION=pgsql`

**Multi-Tenancy Database:**
- Tenant databases: Separate database per tenant via Stancl Tenancy
- Naming: `tenant_{tenant_id}` (prefix-based)
- Central database: Stores tenant metadata, subscriptions, invoices, platform settings
- Tenant connection: Uses `tenant_mysql` configuration template (located in `config/database.php`)
- Connection initialization: Middleware handles automatic tenant context initialization
  - `Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class`
  - `Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class`

**File Storage:**
- Default: Local filesystem
  - Driver: local
  - Path: `storage/app/private`
  - Public path: `storage/app/public`
  - URL: `/storage`
- Optional: AWS S3
  - Driver: s3
  - Config keys: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`
  - Filesystem configuration: `config/filesystems.php`
- Livewire temporary uploads: `storage/app/livewire-tmp`

**Caching:**
- Default: Database-backed cache
  - Driver: database
  - Configured via `CACHE_STORE=database`
- Optional: Redis
  - Configuration: `config/database.php` Redis section
  - Client: phpredis (configured as `REDIS_CLIENT=phpredis`)
  - Connection: `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`

**Session Management:**
- Driver: Database (configured as `SESSION_DRIVER=database`)
- Connection: `SESSION_CONNECTION=mysql` (can differ from app connection)
- Lifetime: `SESSION_LIFETIME=120` minutes
- Secure: `SESSION_SECURE_COOKIE=true` (HTTPS only)
- Encryption: `SESSION_ENCRYPT=false`

**Queue System:**
- Default: Database queue
  - Driver: database
  - Table: `jobs` (configurable via `DB_QUEUE_TABLE`)
  - Connection: Inherits from app database
  - Retry behavior: 90 seconds after failure (configurable via `DB_QUEUE_RETRY_AFTER`)
- Optional drivers configured:
  - Redis queue
  - AWS SQS
  - Beanstalkd
  - Sync (for testing)
- Failed jobs: Stored in `failed_jobs` table via `database-uuids` driver
- Queue jobs in application:
  - `SendBulkSmsJob` - Dispatched for bulk SMS campaigns
  - `SendWelcomeSmsJob` - Sent on member/visitor registration
  - `SendPrayerChainSmsJob` - Prayer request notifications
  - `SendAnnouncementJob` - Announcement message processing
  - `ProcessAnnouncementJob` - Batch processing of announcements
  - `CheckAnnouncementCompletionJob` - Completion status tracking
  - `CreateTenantStorageDirectories` - Tenant initialization

## Authentication & Identity

**Auth Provider:**
- Custom via Laravel Fortify + Built-in Auth
- Implementation: Fortify-based with custom customizations in `FortifyServiceProvider`
- Features enabled (from `config/fortify.php`):
  - Email verification (required)
  - Password reset (enabled)
  - Two-factor authentication (enabled with password + OTP confirmation)
  - Profile information update (enabled)
  - Password change (enabled)
- Registration: **Disabled** - Invitation-only access
  - Feature flag: `Features::registration()` commented out in `config/fortify.php`
- Guard: Web (configured as `guard: web` in fortify config)
- Password reset broker: Default users broker
- Username field: Email (case-insensitive, configured via `BCRYPT_ROUNDS=12`)

**Authorization:**
- Gates and Policies: Used for resource-level authorization
  - Policies located: `app/Policies/`
  - Examples: `SmsTemplatePolicy`, `SmsLogPolicy`, `PrayerRequestPolicy`
- Super Admin: Custom middleware for super admin authentication
  - Middleware: `App\Http\Middleware\SuperAdminAuthenticate`
  - Super admin domain: Configured via `SUPERADMIN_DOMAIN` env var
  - Accessible from central domain (defined in `config/tenancy.php` central_domains array)

**Tenant Context:**
- Middleware: `Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class` and variations
- Fortify middleware: Includes tenancy initialization in routes
- Central domains: Excludes tenancy for admin routes
  - Central domains: localhost, 127.0.0.1, admin.localhost, admin.kingdomvitals.app, kingdomvitals-app.test, admin.kingdomvitals-app.test

## Monitoring & Observability

**Error Tracking:**
- Not detected - Application uses built-in Laravel error handling
- Logging: Via `Illuminate\Support\Facades\Log`

**Logs:**
- Channel: Configured via `LOG_CHANNEL=stack`
- Stack: Single file logging (configured via `LOG_STACK=single`)
- Deprecations: Separate channel (configured via `LOG_DEPRECATIONS_CHANNEL=null`)
- Level: Debug level in local environment (via `LOG_LEVEL=debug`)
- Real-time logs: Laravel Pail can be used via `php artisan pail`
- Log file location: `storage/logs/laravel.log`
- Queue debugging: Custom logging in queue jobs and services (e.g., `TextTangoService` logs API errors)

**Broadcast:**
- Not actively used - Configured as log driver (configured via `BROADCAST_CONNECTION=log`)

## CI/CD & Deployment

**Hosting:**
- Designed for Laravel-compatible hosting platforms:
  - Laravel Forge (recommended)
  - AWS (EC2, Elastic Beanstalk)
  - DigitalOcean App Platform
  - Heroku (with buildpack)
  - Any VPS with PHP 8.2+
- Development: Laravel Herd (local development server)

**CI Pipeline:**
- Not detected in configuration - No GitHub Actions or similar found in standard locations
- Local testing: Run via `composer test` or `php artisan test --compact`
- Code quality: Laravel Pint for formatting checks

## Environment Configuration

**Required env vars (critical for operation):**
- `APP_NAME` - Application display name
- `APP_ENV` - Environment: local, staging, production
- `APP_KEY` - Laravel encryption key (generated via `php artisan key:generate`)
- `APP_DEBUG` - Debug mode (false in production)
- `APP_URL` - Base application URL
- `SUPERADMIN_DOMAIN` - Admin panel domain
- `DB_CONNECTION` - Database driver
- `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` - Database credentials
- `MAIL_MAILER` - Mail driver to use
- `MAIL_FROM_ADDRESS` - Sender email address
- `TEXTTANGO_API_KEY` - SMS service API key
- `TEXTTANGO_SENDER_ID` - SMS sender identifier

**Optional but important:**
- `QUEUE_CONNECTION` - Queue driver (default: database)
- `CACHE_STORE` - Cache driver (default: database)
- `SESSION_DRIVER` - Session driver (default: database)
- `FILESYSTEM_DISK` - File storage driver (default: local)
- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET` - If using S3
- `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` - If using Redis
- `POSTMARK_API_KEY`, `RESEND_API_KEY` - If using email services

**Secrets location:**
- Development: `.env` file (do NOT commit to version control)
- Production: Environment variables via hosting platform
- GitHub Secrets: For CI/CD pipelines (if implemented)
- Laravel Forge: Environment management in dashboard

## Webhooks & Callbacks

**Incoming:**
- TextTango SMS delivery status webhooks
  - Endpoint: `POST /webhooks/texttango`
  - Handler: `App\Http\Controllers\Webhooks\TextTangoWebhookController::handleDelivery()`
  - Validation: Custom `TextTangoWebhookRequest` form request validates payload
  - Webhook secret: `TEXTTANGO_WEBHOOK_SECRET` used for signature verification
  - Functionality: Updates `SmsLog` records with delivery status (Sent, Delivered, Failed, Pending)
  - Multi-tenant awareness: Searches across all tenants to find matching SMS logs

**Outgoing:**
- Email notifications: Via Mail queue (mailable classes in `app/Mail/`)
- SMS campaigns: TextTango API calls via `TextTangoService`
- None detected for other services

---

*Integration audit: 2026-02-03*
