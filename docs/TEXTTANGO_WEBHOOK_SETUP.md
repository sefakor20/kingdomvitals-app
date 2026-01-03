# TextTango SMS Delivery Tracking Webhook Setup

This guide explains how to configure the TextTango webhook for real-time SMS delivery status updates.

## Overview

When SMS messages are sent via TextTango, the webhook receives delivery status updates and automatically updates the `SmsLog` records with:
- `status` (delivered, failed, sent)
- `delivered_at` timestamp
- `error_message` (if delivery failed)

## Deployment Checklist

### Step 1: Generate Webhook Secret

Generate a secure secret key (32+ characters recommended):

```bash
openssl rand -hex 32
```

### Step 2: Add Environment Variable

Add to your production `.env` file:

```env
TEXTTANGO_WEBHOOK_SECRET=your-generated-secret-here
```

### Step 3: Configure TextTango Dashboard

1. Log in to TextTango at https://app.texttango.com
2. Navigate to **Settings > Webhooks** (or API Settings)
3. Add a new webhook with the following configuration:

| Setting | Value |
|---------|-------|
| URL | `https://yourdomain.com/webhooks/texttango/delivery` |
| Method | POST |
| Content-Type | application/json |
| Signature Header | X-TextTango-Signature |
| Secret Key | Same value as `TEXTTANGO_WEBHOOK_SECRET` |

4. Enable delivery status notifications (delivered, failed, etc.)

### Step 4: Verify Webhook is Working

1. Send a test SMS from your application
2. Wait for delivery (usually a few seconds)
3. Check the database:
   ```sql
   SELECT id, phone_number, status, delivered_at, error_message
   FROM sms_logs
   ORDER BY created_at DESC
   LIMIT 5;
   ```
4. The `status` should update from `sent` to `delivered`

## Webhook Endpoint

**URL:** `POST /webhooks/texttango/delivery`

**Expected Payload:**
```json
{
  "tracking_id": "campaign-tracking-id",
  "message_id": "individual-message-id",
  "phone_number": "+233241234567",
  "status": "delivered",
  "error_message": "optional error details",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

**Response:**
```json
{"status": "success"}
```

## Status Mapping

| TextTango Status | App Status |
|------------------|------------|
| delivered, success | Delivered |
| failed, rejected, expired, undeliverable | Failed |
| sent, submitted, accepted | Sent |

## Security

- Webhook uses HMAC-SHA256 signature validation
- Signature is sent in `X-TextTango-Signature` header
- Requests without valid signature return 403 Forbidden
- In local/testing environments, signature validation is bypassed if no secret is configured

## Troubleshooting

### 403 Forbidden Response
- Verify `TEXTTANGO_WEBHOOK_SECRET` in `.env` matches the secret configured in TextTango
- Check that the signature header is being sent correctly

### SmsLog Not Updating
- Verify `provider_message_id` is being saved when SMS is sent
- Check that the `tracking_id` in webhook matches `provider_message_id` in database

### Check Logs
```bash
# View webhook-related logs
tail -f storage/logs/laravel.log | grep TextTango

# Look for these log messages:
# - "TextTango webhook: Delivery status updated" (success)
# - "TextTango webhook: SmsLog not found" (tracking_id mismatch)
# - "TextTango webhook: Invalid signature" (secret mismatch)
```

### Test Webhook Manually
```bash
# Generate signature
SECRET="your-webhook-secret"
PAYLOAD='{"tracking_id":"test-123","phone_number":"+233241234567","status":"delivered"}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | cut -d' ' -f2)

# Send test request
curl -X POST https://yourdomain.com/webhooks/texttango/delivery \
  -H "Content-Type: application/json" \
  -H "X-TextTango-Signature: $SIGNATURE" \
  -d "$PAYLOAD"
```

## Files Reference

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Webhooks/TextTangoWebhookController.php` | Handles webhook requests |
| `app/Http/Requests/TextTangoWebhookRequest.php` | Validates signature and payload |
| `config/services.php` | Contains `texttango.webhook_secret` config |
| `routes/web.php` | Webhook route registration |
| `bootstrap/app.php` | CSRF exception for webhooks |
