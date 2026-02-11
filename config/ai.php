<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider Names
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the AI providers below should be the
    | default for AI operations when no explicit provider is provided
    | for the operation. This should be any provider defined below.
    |
    */

    'default' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
    'default_for_images' => env('AI_DEFAULT_IMAGES_PROVIDER', 'gemini'),
    'default_for_audio' => env('AI_DEFAULT_AUDIO_PROVIDER', 'openai'),
    'default_for_transcription' => env('AI_DEFAULT_TRANSCRIPTION_PROVIDER', 'openai'),
    'default_for_embeddings' => env('AI_DEFAULT_EMBEDDINGS_PROVIDER', 'openai'),
    'default_for_reranking' => env('AI_DEFAULT_RERANKING_PROVIDER', 'cohere'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Chain
    |--------------------------------------------------------------------------
    |
    | When a provider fails, the system can try the next provider in this
    | chain. This ensures resilience when a single provider is unavailable.
    |
    */

    'fallback_chain' => ['anthropic', 'openai', 'ollama'],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Below you may configure caching strategies for AI related operations
    | such as embedding generation. You are free to adjust these values
    | based on your application's available caching stores and needs.
    |
    */

    'caching' => [
        'embeddings' => [
            'cache' => false,
            'store' => env('CACHE_STORE', 'database'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    |
    | Below are each of your AI providers defined for this application. Each
    | represents an AI provider and API key combination which can be used
    | to perform tasks like text, image, and audio creation via agents.
    |
    */

    'providers' => [
        'anthropic' => [
            'driver' => 'anthropic',
            'key' => env('ANTHROPIC_API_KEY'),
        ],

        'cohere' => [
            'driver' => 'cohere',
            'key' => env('COHERE_API_KEY'),
        ],

        'deepseek' => [
            'driver' => 'deepseek',
            'key' => env('DEEPSEEK_API_KEY'),
        ],

        'eleven' => [
            'driver' => 'eleven',
            'key' => env('ELEVENLABS_API_KEY'),
        ],

        'gemini' => [
            'driver' => 'gemini',
            'key' => env('GEMINI_API_KEY'),
        ],

        'groq' => [
            'driver' => 'groq',
            'key' => env('GROQ_API_KEY'),
        ],

        'jina' => [
            'driver' => 'jina',
            'key' => env('JINA_API_KEY'),
        ],

        'mistral' => [
            'driver' => 'mistral',
            'key' => env('MISTRAL_API_KEY'),
        ],

        'ollama' => [
            'driver' => 'ollama',
            'key' => env('OLLAMA_API_KEY', ''),
            'url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        ],

        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
        ],

        'openrouter' => [
            'driver' => 'openrouter',
            'key' => env('OPENROUTER_API_KEY'),
        ],

        'voyageai' => [
            'driver' => 'voyageai',
            'key' => env('VOYAGEAI_API_KEY'),
        ],

        'xai' => [
            'driver' => 'xai',
            'key' => env('XAI_API_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kingdom Vitals AI Features Configuration
    |--------------------------------------------------------------------------
    |
    | Enable/disable individual AI features. Each feature can also specify
    | its own provider override and configuration options.
    |
    */

    'features' => [
        'conversion_prediction' => [
            'enabled' => env('AI_FEATURE_CONVERSION', true),
            'provider' => env('AI_CONVERSION_PROVIDER'), // null = use default
            'cache_ttl' => 86400, // 24 hours
        ],

        'donor_churn' => [
            'enabled' => env('AI_FEATURE_CHURN', true),
            'provider' => env('AI_CHURN_PROVIDER'),
            'days_inactive_threshold' => 90,
        ],

        'message_generation' => [
            'enabled' => env('AI_FEATURE_MESSAGES', true),
            'provider' => env('AI_MESSAGE_PROVIDER'),
            'require_approval' => true,
            'max_tokens' => 300,
        ],

        'attendance_anomaly' => [
            'enabled' => env('AI_FEATURE_ATTENDANCE', true),
            'provider' => env('AI_ATTENDANCE_PROVIDER'),
            'decline_threshold_percent' => 50,
            'weeks_lookback' => 4,
        ],

        'attendance_forecast' => [
            'enabled' => env('AI_FEATURE_FORECAST', true),
            'provider' => env('AI_FORECAST_PROVIDER'),
            'weeks_ahead' => 4,
            'history_weeks' => 12,
        ],

        'prayer_analysis' => [
            'enabled' => env('AI_FEATURE_PRAYER', true),
            'provider' => env('AI_PRAYER_PROVIDER'),
            'auto_analyze' => true,
            'notify_on_critical' => true,
        ],

        'roster_optimization' => [
            'enabled' => env('AI_FEATURE_ROSTER', true),
            'provider' => env('AI_ROSTER_PROVIDER'),
        ],

        'sms_optimization' => [
            'enabled' => env('AI_FEATURE_SMS_OPT', true),
            'provider' => env('AI_SMS_OPT_PROVIDER'),
            'inactivity_threshold_days' => 60,
        ],

        'lifecycle_detection' => [
            'enabled' => env('AI_FEATURE_LIFECYCLE', true),
            'provider' => env('AI_LIFECYCLE_PROVIDER'),
            'new_member_days' => 90,
            'dormant_days' => 90,
            'notify_on_at_risk' => true,
        ],

        'household_engagement' => [
            'enabled' => env('AI_FEATURE_HOUSEHOLD', true),
            'provider' => env('AI_HOUSEHOLD_PROVIDER'),
            'variance_threshold' => 30,
        ],

        'cluster_health' => [
            'enabled' => env('AI_FEATURE_CLUSTER_HEALTH', true),
            'provider' => env('AI_CLUSTER_HEALTH_PROVIDER'),
            'notify_on_struggling' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Heuristic Scoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for heuristic scoring algorithms used as fallback
    | when AI providers are unavailable or disabled.
    |
    */

    'scoring' => [
        'conversion' => [
            'base_score' => 50,
            'return_visit_bonus' => 15,
            'member_referral_bonus' => 10,
            'successful_followup_bonus' => 5,
            'failed_followup_penalty' => 10,
            'recent_attendance_bonus' => 10,
            'weeks_inactive_penalty' => 5,
        ],

        'churn' => [
            'base_score' => 50,
            'days_since_donation_weight' => 0.5,
            'giving_trend_weight' => 0.3,
            'attendance_correlation_weight' => 0.2,
        ],

        'attendance' => [
            'decline_threshold_percent' => 50,
            'baseline_weeks' => 8,
            'comparison_weeks' => 4,
        ],

        'forecast' => [
            'base_confidence' => 70,
            'min_data_weeks' => 4,
            'seasonal_weight' => 0.15,
            'trend_weight' => 0.20,
            'holiday_adjustment' => -0.30,
        ],

        'prayer' => [
            'base_score' => 50,
            'urgency_critical' => 40,
            'urgency_high' => 30,
            'urgency_elevated' => 15,
            'recency_max_bonus' => 10,
            'open_duration_max' => 15,
            'member_bonus' => 2,
            'leaders_only_bonus' => 3,
        ],

        'roster' => [
            'base_score' => 50,
            'fairness_max_bonus' => 20,
            'experience_max_bonus' => 15,
            'reliability_max_bonus' => 15,
            'recency_max_bonus' => 10,
            'conflict_penalty' => 30,
            'overwork_penalty' => 20,
        ],

        'sms_engagement' => [
            'base_score' => 50,
            'delivery_weight' => 15,
            'response_weight' => 30,
            'recency_max_bonus' => 20,
            'consistency_max_bonus' => 15,
            'inactivity_decay_per_week' => 2,
            'opt_out_penalty' => 50,
        ],

        'lifecycle' => [
            'new_member_days' => 90,
            'dormant_days' => 90,
            'churn_risk_disengaging_threshold' => 50,
            'churn_risk_at_risk_threshold' => 70,
            'min_attendance_for_engaged' => 4,
            'min_giving_for_engaged' => 1,
        ],

        'household' => [
            'attendance_weight' => 0.40,
            'giving_weight' => 0.30,
            'lifecycle_weight' => 0.20,
            'sms_engagement_weight' => 0.10,
            'head_member_bonus' => 1.2,
            'variance_threshold' => 30,
        ],

        'cluster' => [
            'attendance_weight' => 0.25,
            'engagement_weight' => 0.20,
            'growth_weight' => 0.20,
            'retention_weight' => 0.20,
            'leadership_weight' => 0.15,
            'meeting_frequency_target' => 4,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Generation Prompts
    |--------------------------------------------------------------------------
    |
    | System prompts for AI message generation features.
    |
    */

    'prompts' => [
        'follow_up_system' => <<<'PROMPT'
You are a friendly church pastoral assistant helping craft follow-up messages for church visitors and members.

Guidelines:
1. Be warm and welcoming, but not pushy
2. Reference specific details when available (visit date, services attended)
3. Keep messages concise and personal
4. Avoid religious jargon unless the person is already engaged
5. Include a gentle invitation to return or connect

IMPORTANT: Return ONLY the message text, no quotes or formatting.
PROMPT,

        'reengagement_system' => <<<'PROMPT'
You are a caring church pastoral assistant helping reconnect with members who haven't attended recently.

Guidelines:
1. Express genuine care and concern
2. Acknowledge that people get busy without being judgmental
3. Share something positive happening at the church
4. Offer support if they're going through difficult times
5. Keep the tone warm and inviting, not guilt-inducing

IMPORTANT: Return ONLY the message text, no quotes or formatting.
PROMPT,
    ],

];
