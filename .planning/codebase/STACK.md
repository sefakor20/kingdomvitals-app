# Technology Stack

**Analysis Date:** 2026-02-03

## Languages

**Primary:**
- PHP 8.2+ - Server-side application logic and backend
- JavaScript (ES6+) - Frontend interactivity and Vite build tooling
- Blade Templates - Server-side HTML templating

**Secondary:**
- SQL - Database queries
- Bash - CLI scripts and Artisan commands

## Runtime

**Environment:**
- PHP 8.2+ (configured as ^8.2 in `composer.json`)
- Node.js 22 (specified in `.nvmrc`)
- Laravel Herd - Development server (serves via https://[project-name].test)

**Package Managers:**
- Composer v2+ - PHP dependency management
- npm v9+ - JavaScript/Node dependency management
- Lockfiles: Both `composer.lock` and `package-lock.json` present and committed

## Frameworks

**Core Backend:**
- Laravel 12.49.0+ - Web framework and application foundation
- Laravel Fortify 1.34+ - Headless authentication backend
- Stancl Tenancy 3.9.1+ - Multi-tenant architecture for per-tenant databases

**Frontend UI:**
- Livewire 4 - Reactive server-driven component framework (via package, not declared in composer.json but pulled via flux)
- Flux UI Free 2.11.1+ - Official Livewire component library for forms, buttons, modals, inputs
- Tailwind CSS 4.0.7+ - Utility-first CSS framework

**Build Tools:**
- Vite 7.0.4+ - Frontend build tool and dev server
- Laravel Vite Plugin 2.0+ - Integrates Vite with Laravel
- Tailwind CSS Vite Plugin 4.1.11+ - Tailwind compilation via Vite

**Testing:**
- Pest 4.3.2+ - PHP testing framework with modern syntax
- PHPUnit 12 (via Pest) - Underlying test runner
- Pest Laravel Plugin 4.0+ - Laravel-specific Pest testing utilities

**Development Tools:**
- Laravel Pint 1.27+ - PHP code formatter and style fixer
- Laravel Debugbar 3.16.5+ - Development debugging toolbar
- Laravel Pail 1.2.4+ - Real-time log viewer
- Laravel Tinker 2.11.0+ - Interactive PHP shell
- Laravel Sail 1.52+ - Docker development environment (optional)
- Rector 2.3.5+ - Automated PHP code modernization
- Laravel Boost 2.0.5+ - MCP server with development tools

## Key Dependencies

**Critical Backend:**
- barryvdh/laravel-dompdf 3.1.1+ - PDF generation for invoices and reports
  - Used by `PlatformInvoicePdfService` and `DonationReceiptService`
  - Generates invoices and donation receipts as PDF attachments
- maatwebsite/excel 3.1.67+ - Excel import/export via PhpSpreadsheet
  - Used by `ReportExportService` for CSV and Excel exports
  - Handles member and visitor bulk imports via `MemberImport` and `VisitorImport`
- intervention/image-laravel 1.5.6+ - Image processing library
  - Used by `ImageProcessingService` for logo and image validation
  - Handles tenant and platform logo uploads
- propaganistas/laravel-phone 6.0.2+ - Phone number validation and formatting
  - Validates phone numbers for SMS functionality

**Frontend:**
- axios 1.7.4+ - HTTP client for AJAX requests
- chart.js 4.5.1+ - JavaScript charting library
  - Used for analytics dashboards and report visualizations
- html5-qrcode 2.3.8+ - QR code scanner for attendance check-in
  - Enables live QR code scanning functionality
- qrcodejs 1.0.0+ - QR code generation
  - Creates QR codes for various features
- autoprefixer 10.4.20+ - PostCSS plugin for vendor prefixes

**Build & CLI:**
- concurrently 9.0.1+ - Runs multiple npm scripts in parallel
  - Used in `composer run dev` to run server, queue, logs, and Vite simultaneously

## Configuration

**Environment:**
- Configuration via `.env` file (copied from `.env.example` during setup)
- Key configs required for operation:
  - `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL`
  - `SUPERADMIN_DOMAIN` - Admin panel domain (e.g., admin.kingdomvitals-app.test)
  - `DB_CONNECTION`, `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`
  - `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_FROM_ADDRESS`
  - `QUEUE_CONNECTION` - Default: database
  - `CACHE_STORE` - Default: database
  - `SESSION_DRIVER` - Default: database
  - `FILESYSTEM_DISK` - Default: local
  - `TEXTTANGO_API_KEY`, `TEXTTANGO_SENDER_ID`, `TEXTTANGO_WEBHOOK_SECRET` - SMS API credentials

**Build:**
- `vite.config.js` - Vite configuration with Laravel and Tailwind plugins
- `tailwind.config.js` - Tailwind CSS configuration (auto-generated via Tailwind)
- `tsconfig.json` - TypeScript configuration (if applicable, minimal setup)
- `.editorconfig` - Editor configuration for consistent formatting
- `.prettierrc` - Not present; uses Pint for PHP and Tailwind for CSS

**Database:**
- Default: SQLite (via `DB_CONNECTION=sqlite` in .env.example)
- Supports: MySQL, MariaDB, PostgreSQL, SQL Server via configuration
- Tenant databases: Uses Stancl Tenancy with separate database per tenant
- Tenant connection template: `tenant_mysql` configuration

## Platform Requirements

**Development:**
- Laravel Herd or equivalent PHP development server
- Node.js 22+ (from `.nvmrc`)
- Composer (PHP dependency manager)
- npm or compatible package manager
- Database: SQLite (default), MySQL (recommended for production)
- Redis (optional, configured but not required)
- Memcached (optional, configured but not required)

**Production:**
- Deployment target: Any Laravel-compatible hosting (Laravel Forge, AWS, DigitalOcean, etc.)
- PHP 8.2+ runtime with extensions: OpenSSL, JSON, Ctype, PDO, Tokenizer
- Database: MySQL/MariaDB or PostgreSQL (SQLite not recommended for production)
- Queue processor: Database driver via `php artisan queue:listen` or supervisor
- Mail service: SMTP or transactional email provider (Postmark, SES, Resend configured)
- File storage: Local filesystem or S3 (AWS S3 configured, optional)

---

*Stack analysis: 2026-02-03*
