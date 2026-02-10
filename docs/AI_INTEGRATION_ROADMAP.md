# Kingdom Vitals AI Integration Roadmap

## Overview

This document outlines AI integration opportunities for Kingdom Vitals church management platform. These features will help churches make data-driven decisions, improve member engagement, and automate repetitive tasks.

---

## PHASE 1: Priority Features (Immediate Implementation)

### 1. Visitor Conversion Prediction
**Problem**: Manually deciding which visitors to prioritize for follow-up
**Solution**: ML model predicting visitor-to-member conversion likelihood

**Data Inputs**:
- Visit frequency and recency
- How they heard about the church
- Follow-up history and outcomes
- Attendance patterns
- Demographics

**Implementation**:
- Add `conversion_score` column to visitors table
- Train classification model on historical conversions
- Display score badge in FollowUpQueue component
- Sort follow-ups by conversion probability
- Weekly batch job to recalculate scores

**Benefit**: Prioritize limited volunteer capacity on highest-potential visitors

---

### 2. Donor Churn & Giving Insights
**Problem**: Identifying lapsing donors only after they've already stopped giving
**Solution**: Predictive churn model + giving capacity assessment

**Data Inputs**:
- Donation history (amounts, frequency, dates)
- Giving trends (increasing/declining)
- Employment and profession data
- Household composition
- Attendance correlation

**Features**:
- **Churn Risk Score**: Predict donors at risk of lapsing (90+ days)
- **Reactivation Likelihood**: Which lapsed donors are most recoverable
- **Giving Capacity Score**: Identify high-potential major gift prospects
- **Engagement Alerts Dashboard**: At-risk donors with confidence scores

**Benefit**: Retain major donors, recover revenue before it's lost

---

### 3. Smart Follow-up Message Generation
**Problem**: Follow-up templates are generic; personalization limited to name fields
**Solution**: LLM-powered personalized message generation via Laravel AI SDK

**Data Inputs**:
- Member/visitor profile
- Follow-up history and notes
- Visit patterns
- Household information
- Recent activities

**Implementation**:
- Use Laravel AI SDK with configurable provider (Claude, GPT-4, Gemini, or local Ollama)
- Context-aware personalization beyond simple placeholders
- Generate multiple message variants for A/B testing
- Human review before sending
- Store generated messages for compliance

**Example Output**:
> "Hi John, we noticed you haven't attended since your visit on January 15th. We'd love to see you at our upcoming Family Service this Sunday - your daughter Emma might enjoy our children's program!"

**Benefit**: Higher engagement rates, more genuine follow-ups

---

### 4. Attendance Pattern Anomaly Detection
**Problem**: Members with significant attendance changes go unnoticed
**Solution**: Anomaly detection on attendance time series

**Data Inputs**:
- 12+ months attendance records
- Service dates and types
- Member demographics
- Life events (recently joined, recently inactive)

**Features**:
- Baseline typical attendance frequency per member
- Flag sudden drops (>50% decrease over 4 weeks)
- Flag sudden increases (potential re-engagement)
- Correlate with giving patterns
- Alert pastoral care team automatically

**Benefit**: Early intervention for struggling members

---

## PHASE 2: Enhanced Analytics (3-6 months)

### 5. Service Attendance Forecasting
**Problem**: Unpredictable attendance makes resource planning difficult
**Solution**: Time series forecasting per service

**Data Inputs**:
- Historical attendance by service
- Seasonal patterns (holidays, summer)
- Special events
- Weather data (optional)

**Benefits**: Better resource planning, improved member experience

---

### 6. Prayer Request Intelligence
**Problem**: Prayer requests are unstructured; no automatic routing
**Solution**: NLP classification + priority scoring

**Features**:
- Auto-categorize prayers (Health, Financial, Family, etc.)
- Detect urgent situations (medical crises, mental health indicators)
- Identify answered prayers from status updates
- Generate weekly prayer summaries for leadership
- Route cluster-specific prayers to appropriate leaders

**Benefit**: Better prayer response, early crisis detection

---

### 7. Duty Roster Optimization
**Problem**: Manual scheduling considering fairness, availability, experience
**Solution**: Constraint satisfaction solver for optimal assignment

**Considerations**:
- Member unavailability dates
- Fair distribution (equal assignment counts)
- Experience level matching
- Team balance (mix experienced + new)
- Leader preferences

**Benefit**: Fairer scheduling, reduced conflicts

---

### 8. SMS Campaign Optimization
**Problem**: SMS sent uniformly; varying engagement rates
**Solution**: Personalized send-time optimization + segmentation

**Features**:
- Predict optimal send time per member
- Segment by engagement level (high/medium/low)
- A/B test message types per segment
- Reduce frequency for low-responders
- Recommend channel (SMS vs email vs in-person)

**Benefit**: Reduced opt-out rates, higher engagement

---

## PHASE 3: Lifecycle & Automation (6-12 months)

### 9. Member Lifecycle Stage Detection
**Problem**: Different members need different communication
**Solution**: Lifecycle stage classification with automated workflows

**Stages**:
1. Visitor
2. New Member (first 90 days)
3. Engaged (regular attendance + giving)
4. Disengaging (declining patterns)
5. Inactive (30+ days absent)
6. At-Risk (churn indicators)

**Automated Triggers**:
- New members: Welcome series
- Disengaging: Re-engagement campaign
- At-risk: Pastor check-in notification

---

### 10. Household Engagement Analysis
**Problem**: Families join with 1-2 active members; others stay inactive
**Solution**: Household-level engagement model

**Features**:
- Identify partially-engaged households
- Recommend family-focused events/clusters
- Track household net giving and growth
- Predict which family members likely to engage

---

### 11. Cluster Health Scoring
**Problem**: Cluster leaders managing manually; inconsistent vitality
**Solution**: Cluster health assessment + AI coaching

**Metrics**:
- Attendance stability
- Member growth/churn
- Meeting frequency
- Leader tenure and effectiveness
- Participation fairness

**Recommendations**:
- Identify struggling clusters early
- Suggest member reassignments
- Recommend potential leaders for growth

---

### 12. Financial Sustainability Forecasting
**Problem**: Hard to forecast future giving; budget uncertainty
**Solution**: Cohort-based forecasting + scenario analysis

**Features**:
- Forecast giving by quarter/year
- "What if" scenario analysis
- Identify giving gaps
- Project impact of new member acquisition

---

## PHASE 4: Advanced Capabilities (12+ months)

### 13. Integrated AI Insights Dashboard
**Problem**: Insights scattered across multiple reports
**Solution**: Unified AI-powered dashboard

**Components**:
- Real-time KPIs with trend arrows
- Health metrics (cluster vitality, volunteer satisfaction)
- Predictive cards ("50 members at churn risk")
- Drill-down capability
- Exportable reports

---

### 14. Natural Language Prayer Summaries
**Problem**: Leaders can't track all prayer requests
**Solution**: Weekly abstractive summaries

**Output Example**:
> "This week: 12 prayer requests - Health (5), Finances (4), Family (3). 3 prayers marked as answered. Urgent: Member requesting prayers for hospitalized family member."

---

### 15. Smart Group/Cluster Recommendations
**Problem**: Members assigned to clusters manually
**Solution**: Recommendation engine for cluster assignments

**Matching Criteria**:
- Geographic proximity
- Age/demographic similarity
- Life stage (newlyweds, families, empty nesters)
- Engagement level
- Interests (from notes/activities)

---

### 16. Event Attendance Prediction
**Problem**: Hard to know who to invite to special events
**Solution**: Event interest prediction model

**Features**:
- Predict likely attendees per event type
- Identify members for targeted personal invites
- Analyze event ROI in terms of engagement lift

---

### 17. Mobile Companion Chatbot
**Problem**: Members can't easily access information
**Solution**: WhatsApp/SMS conversational AI

**Capabilities**:
- Service times and directions
- Small group information
- Prayer request submission
- Giving portal access
- Attendance check-in

---

### 18. Sentiment Analysis of Follow-up Notes
**Problem**: Follow-up notes are unstructured
**Solution**: NLP sentiment + entity extraction

**Features**:
- Flag negative sentiment (potential concerns)
- Extract entities (health issues, job changes)
- Auto-suggest follow-up actions
- Track sentiment trends per member

---

## Technical Architecture

### AI/ML Services

#### Primary: Laravel AI SDK (Prism)
Laravel's official AI package providing a unified interface for multiple AI providers:
```bash
composer require laravel/ai
```

**Benefits**:
- Native Laravel integration
- Provider-agnostic (easily switch between providers)
- Built-in streaming support
- Tool/function calling support
- Structured output handling
- Laravel queue integration

**Supported Providers**:
| Provider | Models | Best For |
|----------|--------|----------|
| Anthropic | Claude 3.5 Sonnet, Claude 3 Opus | Complex reasoning, long context |
| OpenAI | GPT-4o, GPT-4 Turbo | General purpose, function calling |
| Google | Gemini Pro, Gemini Ultra | Multimodal, cost-effective |
| Ollama | Llama 3, Mistral, Phi-3 | Local/private, no API costs |
| Groq | Llama 3, Mixtral | Ultra-fast inference |

#### Provider Selection Strategy
- **Default**: Configure in `config/ai.php` based on cost/quality tradeoffs
- **Per-feature override**: Some features may benefit from specific providers
- **Fallback chain**: Primary → Secondary → Local (for resilience)

**Recommended by Use Case**:
| Feature | Recommended Provider | Reason |
|---------|---------------------|--------|
| Message Generation | Claude/GPT-4 | Natural, pastoral tone |
| Classification | GPT-4o-mini/Gemini | Cost-effective for simple tasks |
| Summarization | Claude | Handles long prayer lists well |
| Chatbot | Any (configurable) | Tenant preference |
| Local/Private | Ollama | No data leaves server |

#### ML Models (Non-LLM)
- **Python/scikit-learn**: Classification models (churn, conversion scores)
- **TensorFlow/PyTorch**: Time series forecasting
- Or use Laravel AI SDK with structured prompts for simpler predictions

### Implementation Patterns

#### Basic Provider-Agnostic Usage
```php
use Laravel\AI\Facades\AI;

// Uses default provider from config
$response = AI::generate('Summarize this prayer request...');

// Explicit provider selection
$response = AI::using('openai')
    ->withSystemPrompt('You are a pastoral care assistant...')
    ->generate($prompt);

// Structured output (works with any provider)
$analysis = AI::using(config('ai.default'))
    ->withSchema(MemberAnalysis::class)
    ->generate($memberContext);
```

#### Provider Fallback Pattern
```php
use Laravel\AI\Facades\AI;

class AiService
{
    protected array $providers = ['anthropic', 'openai', 'ollama'];

    public function generate(string $prompt): string
    {
        foreach ($this->providers as $provider) {
            try {
                return AI::using($provider)->generate($prompt);
            } catch (ProviderException $e) {
                Log::warning("AI provider {$provider} failed", ['error' => $e->getMessage()]);
                continue;
            }
        }
        throw new AllProvidersFailedException();
    }
}
```

#### Tenant-Configurable Provider
```php
// Allow each church to choose their preferred provider
$provider = $tenant->ai_provider ?? config('ai.default');

$response = AI::using($provider)
    ->withSystemPrompt($this->getSystemPrompt())
    ->generate($prompt);
```

### Processing Pipeline
- **Laravel Queues**: Async AI processing
- **Laravel Scheduler**: Daily/weekly batch jobs for score recalculation
- **Cache**: Store predictions (7-day TTL)

### Data Storage
- Add prediction columns to existing tables
- Cache predictions (7-day TTL)
- Audit trail for all predictions

### Privacy & Ethics
- All predictions private to leaders only
- Bias monitoring by demographic
- Transparent explanations for predictions
- GDPR/CCPA compliance
- Consent for member-facing features

---

## Quick Wins (No ML Required)

1. **Enhanced Inactive Member Alerts**: Email pastors list of members inactive 60+ days
2. **Donor Trend Sparklines**: Visual trends on donor dashboard
3. **Search Autocomplete**: Suggest popular searches from analytics
4. **Visit Frequency Heatmap**: Visualize service attendance patterns
5. **Template Suggestions**: Recommend follow-up templates based on visitor status

---

## Success Metrics

### Financial Impact
- Donor retention: +5-10%
- Lapsed donor reactivation: 20%+
- Average donation increase: +2-5%

### Member Engagement
- Visitor conversion: +15-25%
- Attendance consistency: +10%
- Prayer response time: -50%

### Operational Efficiency
- Duty roster scheduling time: -70%
- Follow-up completion rate: +30%
- Pastoral staff time saved: 10+ hours/month

---

## Implementation Timeline

| Phase | Features | Timeline |
|-------|----------|----------|
| 1 | Visitor Conversion, Donor Churn, Smart Follow-ups, Attendance Alerts | Q1 2026 |
| 2 | Forecasting, Prayer Intelligence, Duty Roster, SMS Optimization | Q2-Q3 2026 |
| 3 | Lifecycle Detection, Household Analysis, Cluster Health | Q4 2026 |
| 4 | Advanced Dashboard, Chatbot, Sentiment Analysis | 2027 |

---

## Configuration & Setup

### Installation
```bash
composer require laravel/ai
php artisan vendor:publish --tag=ai-config
```

### Environment Variables
```env
# Default provider (anthropic, openai, google, ollama, groq)
AI_DEFAULT_PROVIDER=anthropic

# Anthropic (Claude)
ANTHROPIC_API_KEY=your-key-here

# OpenAI (GPT-4)
OPENAI_API_KEY=your-key-here

# Google (Gemini)
GOOGLE_AI_API_KEY=your-key-here

# Groq (fast inference)
GROQ_API_KEY=your-key-here

# Ollama (local - no key needed)
OLLAMA_HOST=http://localhost:11434
```

### Config File (config/ai.php)
```php
return [
    'default' => env('AI_DEFAULT_PROVIDER', 'anthropic'),

    'providers' => [
        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => 'claude-3-5-sonnet-20241022',
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => 'gpt-4o',
        ],
        'google' => [
            'api_key' => env('GOOGLE_AI_API_KEY'),
            'model' => 'gemini-pro',
        ],
        'ollama' => [
            'host' => env('OLLAMA_HOST', 'http://localhost:11434'),
            'model' => 'llama3',
        ],
    ],

    // Feature-specific provider overrides
    'features' => [
        'follow_up_messages' => env('AI_FOLLOWUP_PROVIDER'),
        'prayer_summaries' => env('AI_PRAYER_PROVIDER'),
        'chatbot' => env('AI_CHATBOT_PROVIDER'),
    ],
];
```

### Cost Optimization Tips
1. Use smaller/cheaper models for classification tasks (GPT-4o-mini, Gemini Flash)
2. Cache AI responses where appropriate (prayer summaries, cluster recommendations)
3. Batch requests during off-peak hours via Laravel queues
4. Consider Ollama for development/testing to avoid API costs
5. Allow tenants to bring their own API keys for reduced platform costs

---

## Getting Started

To begin implementing Phase 1 features:

1. Install Laravel AI SDK and publish config (see above)
2. Configure at least one provider's API key in `.env`
3. Start with Smart Follow-up Messages (quickest to implement with immediate value)
4. Use Ollama locally for development to save on API costs

---

*Last Updated: February 2026*
*Version: 1.1 - Multi-provider support*
