<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Donation;
use App\Models\Tenant\PaymentTransaction;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $event = $request->input('event');
        $data = $request->input('data', []);

        Log::info('Paystack webhook received', [
            'event' => $event,
            'reference' => $data['reference'] ?? null,
        ]);

        // Find the transaction to get the branch for signature validation
        $reference = $data['reference'] ?? null;
        if (! $reference) {
            return response()->json(['status' => 'error', 'message' => 'No reference'], 400);
        }

        $transaction = PaymentTransaction::where('paystack_reference', $reference)->first();
        if (! $transaction) {
            Log::warning('Paystack webhook: Transaction not found', ['reference' => $reference]);

            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }

        // Validate signature
        $signature = $request->header('x-paystack-signature');
        if (! $signature) {
            Log::warning('Paystack webhook: Missing signature', ['reference' => $reference]);

            return response()->json(['status' => 'error', 'message' => 'Missing signature'], 401);
        }

        $branch = $transaction->branch;
        $paystack = PaystackService::forBranch($branch);

        if (! $paystack->validateWebhookSignature($payload, $signature)) {
            Log::warning('Paystack webhook: Invalid signature', ['reference' => $reference]);

            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
        }

        // Handle the event
        return match ($event) {
            'charge.success' => $this->handleChargeSuccess($transaction, $data),
            'charge.failed' => $this->handleChargeFailed($transaction, $data),
            'subscription.create' => $this->handleSubscriptionCreated($transaction, $data),
            'subscription.disable' => $this->handleSubscriptionDisabled($data),
            'invoice.create' => $this->handleInvoiceCreated($data),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($data),
            default => response()->json(['status' => 'ignored', 'event' => $event]),
        };
    }

    protected function handleChargeSuccess(PaymentTransaction $transaction, array $data): JsonResponse
    {
        // Skip if already processed
        if ($transaction->isSuccessful()) {
            return response()->json(['status' => 'already_processed']);
        }

        // Verify the transaction
        $paystack = PaystackService::forBranch($transaction->branch);
        $verification = $paystack->verifyTransaction($transaction->paystack_reference);

        if (! $verification['success']) {
            Log::error('Paystack webhook: Verification failed', [
                'reference' => $transaction->paystack_reference,
                'error' => $verification['error'] ?? 'Unknown',
            ]);

            return response()->json(['status' => 'error', 'message' => 'Verification failed'], 400);
        }

        $paystackData = $verification['data'];

        // Update transaction
        $transaction->markAsSuccessful(
            (string) ($paystackData['id'] ?? ''),
            $paystackData['channel'] ?? null
        );

        // Create donation if not exists
        if (! $transaction->donation_id) {
            $metadata = $transaction->metadata ?? [];

            $donation = Donation::create([
                'branch_id' => $transaction->branch_id,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'donation_type' => $metadata['donation_type'] ?? 'offering',
                'payment_method' => PaymentMethod::Paystack,
                'donation_date' => now()->toDateString(),
                'reference_number' => $transaction->paystack_reference,
                'donor_name' => ($metadata['is_anonymous'] ?? false) ? null : ($metadata['donor_name'] ?? null),
                'donor_email' => $metadata['donor_email'] ?? null,
                'donor_phone' => $metadata['donor_phone'] ?? null,
                'is_anonymous' => $metadata['is_anonymous'] ?? false,
                'is_recurring' => $metadata['is_recurring'] ?? false,
                'recurring_interval' => $metadata['recurring_interval'] ?? null,
                'notes' => $metadata['notes'] ?? null,
                'paystack_customer_code' => $paystackData['customer']['customer_code'] ?? null,
            ]);

            $transaction->update(['donation_id' => $donation->id]);
        }

        Log::info('Paystack webhook: Charge success processed', [
            'reference' => $transaction->paystack_reference,
            'amount' => $transaction->amount,
        ]);

        return response()->json(['status' => 'success']);
    }

    protected function handleChargeFailed(PaymentTransaction $transaction, array $data): JsonResponse
    {
        $transaction->markAsFailed();

        Log::info('Paystack webhook: Charge failed', [
            'reference' => $transaction->paystack_reference,
            'message' => $data['gateway_response'] ?? 'Unknown',
        ]);

        return response()->json(['status' => 'success']);
    }

    protected function handleSubscriptionCreated(PaymentTransaction $transaction, array $data): JsonResponse
    {
        $subscriptionCode = $data['subscription_code'] ?? null;

        if ($subscriptionCode && $transaction->donation) {
            $transaction->donation->update([
                'paystack_subscription_code' => $subscriptionCode,
            ]);

            Log::info('Paystack webhook: Subscription created', [
                'subscription_code' => $subscriptionCode,
                'donation_id' => $transaction->donation_id,
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleSubscriptionDisabled(array $data): JsonResponse
    {
        $subscriptionCode = $data['subscription_code'] ?? null;

        if ($subscriptionCode) {
            Donation::where('paystack_subscription_code', $subscriptionCode)
                ->update([
                    'is_recurring' => false,
                    'paystack_subscription_code' => null,
                ]);

            Log::info('Paystack webhook: Subscription disabled', [
                'subscription_code' => $subscriptionCode,
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleInvoiceCreated(array $data): JsonResponse
    {
        // Handle recurring payment invoice
        $subscriptionCode = $data['subscription']['subscription_code'] ?? null;
        $amount = isset($data['amount']) ? PaystackService::fromKobo((int) $data['amount']) : 0;

        Log::info('Paystack webhook: Invoice created for recurring payment', [
            'subscription_code' => $subscriptionCode,
            'amount' => $amount,
        ]);

        return response()->json(['status' => 'success']);
    }

    protected function handleInvoicePaymentFailed(array $data): JsonResponse
    {
        $subscriptionCode = $data['subscription']['subscription_code'] ?? null;

        Log::warning('Paystack webhook: Recurring payment failed', [
            'subscription_code' => $subscriptionCode,
        ]);

        return response()->json(['status' => 'success']);
    }
}
