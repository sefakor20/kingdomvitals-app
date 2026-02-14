# AI Features Production Deployment Guide

This guide covers deploying Kingdom Vitals AI features to production. The platform includes 16 AI-powered capabilities for church management insights.

---

## Table of Contents

1. [Overview](#overview)
2. [Environment Variables](#environment-variables)
3. [Database Setup](#database-setup)
4. [Queue Configuration](#queue-configuration)
5. [Scheduled Tasks](#scheduled-tasks)
6. [Feature Flags](#feature-flags)
7. [Alert Configuration](#alert-configuration)
8. [Deployment Checklist](#deployment-checklist)
9. [Monitoring](#monitoring)
10. [Troubleshooting](#troubleshooting)

---

## Overview

### AI Features Included

| Feature | Description | Scheduled |
|---------|-------------|-----------|
| Visitor Conversion Scoring | Predict visitor-to-member conversion likelihood | Weekly |
| Donor Churn Detection | Identify at-risk donors before they stop giving | Weekly |
| Attendance Anomaly Detection | Detect unusual attendance patterns | Daily |
| Attendance Forecasting | Predict future attendance numbers | Weekly |
| Financial Forecasting | Project giving trends with confidence scores | Weekly |
| Giving Trend Analysis | Analyze donation patterns and classify donors | Weekly |
| Member Lifecycle Detection | Track member engagement stages | Weekly |
| Household Engagement Scoring | Score household-level engagement | Weekly |
| Cluster Health Assessment | Monitor small group health metrics | Weekly |
| Prayer Request Analysis | Analyze and categorize prayer requests | Hourly |
| Prayer Summaries | Generate weekly/monthly prayer summaries | Weekly/Monthly |
| SMS Engagement Optimization | Optimize SMS campaign timing | Weekly |
| Duty Roster Optimization | AI-powered volunteer scheduling | Weekly |
| AI Alerts & Notifications | Real-time alerts for critical insights | Daily |
| Alert Recommendations | Suggested actions for each alert | On-demand |
| Member Recommendations | Suggest group placements for members | On-demand |

### Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Laravel Application                       │
├─────────────────────────────────────────────────────────────────┤
│  config/ai.php          │  16 AI Services          │  23 Jobs   │
│  - Provider config      │  - Scoring algorithms    │  - Queued  │
│  - Feature flags        │  - Heuristic analysis    │  - Scheduled│
│  - Thresholds           │  - API integrations      │  - Chunked │
├─────────────────────────────────────────────────────────────────┤
│                        External APIs                             │
│  Anthropic │ OpenAI │ Gemini │ Cohere │ Ollama (self-hosted)    │
└─────────────────────────────────────────────────────────────────┘
```

---

## Environment Variables

### Required API Keys

At minimum, configure one primary AI provider:

```env
# Primary AI Provider (Required - choose one)
ANTHROPIC_API_KEY=sk-ant-...          # Recommended primary provider

# Additional Providers (Optional but recommended)
OPENAI_API_KEY=sk-...                  # For audio, transcription, embeddings
GEMINI_API_KEY=...                     # For image processing
COHERE_API_KEY=...                     # For text reranking
```

### Provider Selection

Configure which provider handles each capability:

```env
# Default Providers
AI_DEFAULT_PROVIDER=anthropic
AI_DEFAULT_IMAGES_PROVIDER=gemini
AI_DEFAULT_AUDIO_PROVIDER=openai
AI_DEFAULT_TRANSCRIPTION_PROVIDER=openai
AI_DEFAULT_EMBEDDINGS_PROVIDER=openai
AI_DEFAULT_RERANKING_PROVIDER=cohere

# Fallback Chain (comma-separated)
# If primary fails, tries next in chain
AI_FALLBACK_CHAIN=anthropic,openai,ollama
```

### Self-Hosted Option (Ollama)

For organizations requiring on-premise AI:

```env
OLLAMA_BASE_URL=http://localhost:11434
# No API key needed for Ollama
```

### All Supported Providers

| Provider | Environment Variable | Use Case |
|----------|---------------------|----------|
| Anthropic | `ANTHROPIC_API_KEY` | Primary text generation |
| OpenAI | `OPENAI_API_KEY` | Audio, embeddings |
| Google Gemini | `GEMINI_API_KEY` | Image analysis |
| Cohere | `COHERE_API_KEY` | Text reranking |
| Ollama | `OLLAMA_BASE_URL` | Self-hosted LLM |
| Mistral | `MISTRAL_API_KEY` | Alternative provider |
| Groq | `GROQ_API_KEY` | Fast inference |
| DeepSeek | `DEEPSEEK_API_KEY` | Alternative provider |
| OpenRouter | `OPENROUTER_API_KEY` | Multi-model access |
| xAI (Grok) | `XAI_API_KEY` | Alternative provider |
| ElevenLabs | `ELEVENLABS_API_KEY` | Audio synthesis |
| Voyage AI | `VOYAGEAI_API_KEY` | Embeddings |
| Jina | `JINA_API_KEY` | Web search |

---

## Database Setup

### Required Migrations

AI features require these database tables:

| Table | Purpose |
|-------|---------|
| `ai_alerts` | Stores generated alerts with severity, type, and polymorphic relations |
| `ai_alert_settings` | Per-branch alert configuration (thresholds, channels, cooldowns) |
| `attendance_forecasts` | Attendance predictions with confidence scores |
| `prayer_summaries` | Aggregated prayer analysis by period |

Additional columns are added to existing tables:
- `visitors.conversion_score`, `conversion_factors`, `conversion_score_calculated_at`
- `households` - engagement scoring columns
- `clusters` - health metric columns

### Running Migrations

For multi-tenant setup (Stancl Tenancy):

```bash
# Migrate all tenant databases
php artisan tenants:migrate

# Or migrate specific tenant
php artisan tenants:migrate --tenants=church-uuid
```

For single-tenant setup:

```bash
php artisan migrate
```

### Verify Tables Created

```bash
php artisan tinker --execute="
    \$tables = ['ai_alerts', 'ai_alert_settings', 'attendance_forecasts', 'prayer_summaries'];
    foreach (\$tables as \$table) {
        echo \$table . ': ' . (Schema::hasTable(\$table) ? 'OK' : 'MISSING') . PHP_EOL;
    }
"
```

---

## Queue Configuration

### Queue Backend

AI jobs are queued for background processing. Choose a queue backend:

```env
# Option 1: Redis (Recommended for production)
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# Option 2: Database (Simple setup)
QUEUE_CONNECTION=database

# Option 3: Amazon SQS
QUEUE_CONNECTION=sqs
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
SQS_QUEUE=...
```

### Worker Configuration

AI jobs require extended timeouts:

```bash
# Start worker with appropriate timeout
php artisan queue:work --timeout=600 --tries=3 --memory=512
```

### Supervisor Configuration

Create `/etc/supervisor/conf.d/kingdomvitals-ai-worker.conf`:

```ini
[program:kingdomvitals-ai-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/kingdomvitals/artisan queue:work redis --sleep=3 --tries=3 --timeout=600 --memory=512
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/kingdomvitals/ai-worker.log
stopwaitsecs=3600
```

Apply configuration:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start kingdomvitals-ai-worker:*
```

### Job Specifications

| Job | Timeout | Retries | Chunk Size |
|-----|---------|---------|------------|
| CalculateVisitorConversionScoresJob | 300s | 3 | 50 |
| CalculateDonorChurnScoresJob | 300s | 3 | - |
| DetectAttendanceAnomaliesJob | 300s | 3 | - |
| GenerateAttendanceForecastsJob | 300s | 3 | - |
| ProcessAiAlertsJob | 600s | 3 | - |
| AnalyzeGivingTrendsJob | 600s | 3 | - |
| All other AI jobs | 300s | 3 | - |

---

## Scheduled Tasks

### Cron Setup

Add single cron entry for Laravel scheduler:

```bash
# Edit crontab
crontab -e

# Add this line
* * * * * cd /var/www/kingdomvitals && php artisan schedule:run >> /dev/null 2>&1
```

### AI Task Schedule

All times are in UTC. Adjust `config/app.php` timezone as needed.

#### Daily Tasks

| Time | Command | Description |
|------|---------|-------------|
| 04:00 | `ai:detect-anomalies` | Detect attendance anomalies |
| 08:00 | `ai:send-alert-digest` | Send daily alert digest emails |
| 09:00 | `ai:process-alerts` | Generate and process AI alerts |

#### Weekly Tasks (Monday)

| Time | Command | Description |
|------|---------|-------------|
| 02:00 | `ai:optimize-roster-scores` | Optimize volunteer duty roster |
| 03:00 | `ai:recalculate-scores` | Recalculate conversion & churn scores |
| 04:00 | `ai:detect-lifecycle-stages` | Detect member lifecycle changes |
| 05:00 | `ai:calculate-household-engagement` | Calculate household engagement |
| 06:00 | `ai:calculate-cluster-health` | Calculate cluster/group health |
| 07:00 | `ai:generate-prayer-summaries --period=weekly` | Generate weekly prayer summary |
| 08:00 | `ai:forecast-financial --type=monthly --periods=4` | Generate financial forecasts |

#### Weekly Tasks (Tuesday)

| Time | Command | Description |
|------|---------|-------------|
| 02:00 | `ai:calculate-sms-engagement` | Calculate SMS engagement scores |
| 03:00 | `ai:analyze-giving-trends` | Analyze giving trends |

#### Weekly Tasks (Sunday)

| Time | Command | Description |
|------|---------|-------------|
| 23:00 | `ai:forecast-attendance --weeks=4` | Generate attendance forecasts |

#### Hourly Tasks

| Frequency | Command | Description |
|-----------|---------|-------------|
| Every hour | `ai:analyze-prayers` | Analyze new prayer requests |

#### Monthly Tasks

| Time | Command | Description |
|------|---------|-------------|
| 1st at 07:00 | `ai:generate-prayer-summaries --period=monthly` | Generate monthly prayer summary |

### Verify Schedule

```bash
php artisan schedule:list
```

---

## Feature Flags

### Enable/Disable Individual Features

Each AI feature can be independently toggled:

```env
# Core AI Features
AI_FEATURE_CONVERSION=true              # Visitor conversion prediction
AI_FEATURE_CHURN=true                   # Donor churn detection
AI_FEATURE_MESSAGES=true                # AI message generation
AI_FEATURE_ATTENDANCE=true              # Attendance anomaly detection
AI_FEATURE_FORECAST=true                # Attendance forecasting
AI_FEATURE_PRAYER=true                  # Prayer request analysis
AI_FEATURE_ROSTER=true                  # Duty roster optimization
AI_FEATURE_SMS_OPT=true                 # SMS engagement optimization
AI_FEATURE_LIFECYCLE=true               # Member lifecycle detection
AI_FEATURE_HOUSEHOLD=true               # Household engagement scoring
AI_FEATURE_CLUSTER_HEALTH=true          # Cluster health assessment
AI_FEATURE_FINANCIAL_FORECAST=true      # Financial forecasting
AI_FEATURE_GIVING_TRENDS=true           # Giving trend analysis
AI_FEATURE_RECOMMENDATIONS=true         # Alert recommendations
AI_FEATURE_MEMBER_RECOMMENDATION=true   # Member group recommendations

# Master Alert Toggle
AI_ALERTS_ENABLED=true                  # Enable/disable all alerts
```

### Per-Feature Provider Override

Override the default provider for specific features:

```env
AI_CONVERSION_PROVIDER=anthropic
AI_CHURN_PROVIDER=openai
AI_FINANCIAL_FORECAST_PROVIDER=anthropic
# Leave empty to use default provider
```

### Verify Configuration

```bash
php artisan tinker --execute="dd(config('ai.features'));"
```

---

## Alert Configuration

### Alert Types

| Type | Severity | Description |
|------|----------|-------------|
| `churn_risk` | high/medium | Donor at risk of stopping giving |
| `attendance_anomaly` | high/medium | Unusual attendance pattern |
| `cluster_health_critical` | high | Small group health below threshold |
| `lifecycle_at_risk` | medium | Member showing disengagement |
| `visitor_high_potential` | low | Visitor with high conversion score |

### Default Thresholds

Configured in `config/ai.php`:

```php
'alerts' => [
    'enabled' => true,
    'default_cooldown_hours' => 24,
    'send_immediate_notifications' => true,
    'send_daily_digest' => true,
    'digest_hour' => 8,
    'thresholds' => [
        'churn_risk' => 70,              // Alert if score >= 70%
        'attendance_anomaly' => 50,       // Alert if anomaly score >= 50%
        'cluster_health_critical' => 30,  // Alert if health <= 30%
        'cluster_health_struggling' => 50,
    ],
],
```

### Per-Branch Configuration

Alert settings are configurable per branch via `ai_alert_settings` table:
- Enable/disable specific alert types
- Customize thresholds
- Configure notification channels (email, SMS, in-app)
- Set recipient roles
- Configure cooldown periods

---

## Deployment Checklist

### Pre-Deployment

```markdown
[ ] API keys obtained and stored securely
    - [ ] Anthropic API key (primary)
    - [ ] OpenAI API key (recommended)
    - [ ] Other providers as needed

[ ] Infrastructure ready
    - [ ] Redis/queue backend configured
    - [ ] Supervisor installed
    - [ ] Cron daemon running

[ ] Configuration complete
    - [ ] All AI_FEATURE_* flags set
    - [ ] Provider selection configured
    - [ ] Alert thresholds reviewed

[ ] Database backup created
```

### Deployment

```bash
# 1. Deploy code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Run migrations
php artisan tenants:migrate

# 4. Clear and cache config
php artisan config:cache
php artisan route:cache

# 5. Restart queue workers
sudo supervisorctl restart kingdomvitals-ai-worker:*

# 6. Verify cron is active
crontab -l | grep schedule:run
```

### Post-Deployment Verification

```bash
# Test AI configuration loaded
php artisan tinker --execute="dd(config('ai.providers'));"

# Test single job manually (synchronous)
php artisan ai:process-alerts --sync

# Verify queue workers running
php artisan queue:monitor default

# Check scheduled tasks registered
php artisan schedule:list | grep ai:

# Test database tables exist
php artisan tinker --execute="echo App\Models\AiAlert::count();"
```

---

## Monitoring

### Log Locations

AI operations log to Laravel's default log:

```bash
tail -f storage/logs/laravel.log | grep -E "(AI|Job|Alert)"
```

### Key Log Entries

```
[INFO] CalculateVisitorConversionScoresJob: Starting for branch {id}
[INFO] CalculateVisitorConversionScoresJob: Completed (processed: 150, errors: 0)
[WARNING] AI provider anthropic failed - attempting fallback to openai
[INFO] ProcessAiAlertsJob: Generated 5 alerts, sent 3 notifications
[INFO] SendAiAlertDigestJob: Sent digest to 12 recipients (15 alerts)
```

### Metrics to Monitor

| Metric | Location | Alert Threshold |
|--------|----------|-----------------|
| Failed jobs | `failed_jobs` table | > 5 per hour |
| Queue depth | Redis/database | > 100 pending |
| API errors | Application logs | > 10 per hour |
| Alert generation | `ai_alerts` table | 0 new in 48 hours |

### Health Check Endpoint

Add to monitoring system:

```php
// routes/api.php
Route::get('/health/ai', function () {
    return response()->json([
        'status' => 'ok',
        'alerts_enabled' => config('ai.features.alerts.enabled'),
        'providers' => [
            'primary' => config('ai.default_provider'),
            'configured' => !empty(config('ai.providers.anthropic.api_key')),
        ],
        'recent_alerts' => \App\Models\AiAlert::where('created_at', '>', now()->subDay())->count(),
    ]);
});
```

---

## Troubleshooting

### Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| Jobs not processing | Queue worker not running | `sudo supervisorctl status` and restart if needed |
| "API key not configured" | Missing env variable | Verify `ANTHROPIC_API_KEY` in `.env` |
| Alerts not generating | Feature disabled | Check `AI_ALERTS_ENABLED=true` |
| Timeout errors | Job exceeds limit | Increase `--timeout` in supervisor config |
| "No tenant set" | Multi-tenant context missing | Ensure jobs run within tenant context |
| Fallback exhausted | All providers failing | Check API quotas and network connectivity |
| Duplicate alerts | Cooldown not respected | Verify `ai_alert_settings.cooldown_hours` |

### Debug Commands

```bash
# Test specific AI service
php artisan tinker --execute="
    tenancy()->initialize(\App\Models\Tenant::first());
    \$service = app(\App\Services\AI\DonorChurnService::class);
    dd(\$service->calculateChurnRisk(\App\Models\Member::first()));
"

# Check job failures
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear stuck jobs
php artisan queue:flush
```

### API Rate Limiting

If hitting provider rate limits:

1. Stagger job schedules across hours
2. Implement local caching for repeated analyses
3. Consider upgrading API tier
4. Use multiple providers with rotation

### Performance Optimization

For large churches (10,000+ members):

1. Increase worker count: `numprocs=4` in supervisor
2. Use Redis for queues (faster than database)
3. Enable chunk processing in jobs
4. Consider dedicated queue for AI jobs:
   ```bash
   php artisan queue:work --queue=ai-jobs --timeout=600
   ```

---

## Security Considerations

### API Key Storage

- Never commit API keys to version control
- Use environment variables or secrets manager
- Rotate keys periodically
- Monitor API usage for anomalies

### Data Privacy

- AI analysis runs on your infrastructure
- Member data is not sent to AI providers unless generating messages
- Prayer request analysis can be disabled if sensitive
- Configure data retention for AI tables

### Access Control

- AI dashboard requires appropriate permissions
- Alert acknowledgment is logged with user ID
- Audit trail maintained for AI-generated actions

---

## Support

For issues with AI features:

1. Check this documentation
2. Review application logs
3. Verify configuration with `php artisan tinker`
4. Contact support with:
   - Error messages from logs
   - Configuration dump (redact API keys)
   - Steps to reproduce
