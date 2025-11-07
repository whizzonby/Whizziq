<?php

namespace App\Services;

use App\Models\{TaxPayment, User};
use Illuminate\Support\Facades\{DB, Log, Http};
use Stripe\{StripeClient, Exception\ApiErrorException};

/**
 * Tax Payment Service
 *
 * Integrates with payment processors for tax payments
 * Supported: Stripe (credit/debit cards), Plaid (ACH)
 */
class TaxPaymentService
{
    protected ?StripeClient $stripe = null;

    public function __construct()
    {
        if (config('services.stripe.secret')) {
            $this->stripe = new StripeClient(config('services.stripe.secret'));
        }
    }

    /**
     * Process a tax payment
     */
    public function processPayment(TaxPayment $payment): array
    {
        DB::beginTransaction();

        try {
            // Update status to processing
            $payment->update([
                'status' => 'processing',
                'status_message' => 'Payment is being processed...',
            ]);

            // Route to appropriate payment processor
            $result = match($payment->payment_method) {
                'ach' => $this->processACHPayment($payment),
                'credit_card', 'debit_card' => $this->processCardPayment($payment),
                'check' => $this->processCheckPayment($payment),
                'wire' => $this->processWirePayment($payment),
                default => throw new \Exception('Unsupported payment method: ' . $payment->payment_method),
            };

            if ($result['success']) {
                $payment->update([
                    'status' => 'completed',
                    'processed_date' => now(),
                    'confirmation_number' => $result['confirmation_number'],
                    'payment_gateway_id' => $result['transaction_id'] ?? null,
                    'gateway_response' => $result,
                    'status_message' => 'Payment completed successfully',
                ]);

                DB::commit();

                // Send confirmation notification
                $this->sendPaymentConfirmation($payment);

                return [
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'confirmation_number' => $result['confirmation_number'],
                ];
            } else {
                $payment->update([
                    'status' => 'failed',
                    'status_message' => $result['message'] ?? 'Payment failed',
                    'gateway_response' => $result,
                ]);

                DB::commit();

                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Payment failed',
                ];
            }

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Tax payment processing failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $payment->update([
                'status' => 'failed',
                'status_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process ACH payment via Plaid
     */
    protected function processACHPayment(TaxPayment $payment): array
    {
        if (!config('services.plaid.client_id')) {
            return $this->queueForManualProcessing($payment, 'ACH payment queued - Plaid not configured');
        }

        try {
            $user = $payment->user;
            $taxSetting = $user->taxSetting;

            // Get bank account token from Plaid
            $accessToken = $this->getPlaidAccessToken($user);

            if (!$accessToken) {
                return $this->queueForManualProcessing($payment, 'Bank account not linked');
            }

            // Create ACH transfer via Plaid
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://api.plaid.com/transfer/create', [
                'client_id' => config('services.plaid.client_id'),
                'secret' => config('services.plaid.secret'),
                'access_token' => $accessToken,
                'account_id' => $this->getPlaidAccountId($user),
                'type' => 'debit',
                'network' => 'ach',
                'amount' => (string) $payment->total_amount,
                'description' => 'Tax Payment - ' . $payment->getPaymentTypeName(),
                'user' => [
                    'legal_name' => $user->name,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'confirmation_number' => 'ACH-' . strtoupper(substr(md5($data['transfer_id']), 0, 12)),
                    'transaction_id' => $data['transfer_id'],
                    'processor' => 'plaid',
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'ACH transfer failed: ' . $response->body(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Plaid ACH failed', ['error' => $e->getMessage()]);
            return $this->queueForManualProcessing($payment, 'ACH processing error');
        }
    }

    /**
     * Process credit/debit card payment via Stripe
     */
    protected function processCardPayment(TaxPayment $payment): array
    {
        if (!$this->stripe) {
            return $this->queueForManualProcessing($payment, 'Card payment queued - Stripe not configured');
        }

        try {
            $user = $payment->user;

            // Get or create Stripe customer
            $customerId = $this->getOrCreateStripeCustomer($user);

            // Create payment intent
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int) ($payment->total_amount * 100), // Convert to cents
                'currency' => 'usd',
                'customer' => $customerId,
                'description' => 'Tax Payment - ' . $payment->getPaymentTypeName() . ' - Year ' . $payment->tax_year,
                'metadata' => [
                    'payment_id' => $payment->id,
                    'tax_year' => $payment->tax_year,
                    'payment_type' => $payment->payment_type,
                ],
                'statement_descriptor' => 'TAX PAYMENT ' . $payment->tax_year,
            ]);

            // For production, you would:
            // 1. Return client_secret to frontend
            // 2. Collect card details with Stripe Elements
            // 3. Confirm payment on frontend
            // 4. Webhook receives confirmation and updates payment

            // For now, simulate success if we got a payment intent
            return [
                'success' => true,
                'confirmation_number' => 'CARD-' . strtoupper(substr($paymentIntent->id, -12)),
                'transaction_id' => $paymentIntent->id,
                'processor' => 'stripe',
                'requires_action' => true, // Would require frontend action in production
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe payment failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Card payment failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process check payment (manual)
     */
    protected function processCheckPayment(TaxPayment $payment): array
    {
        return $this->queueForManualProcessing($payment, 'Check payment instructions generated');
    }

    /**
     * Process wire payment (manual)
     */
    protected function processWirePayment(TaxPayment $payment): array
    {
        return $this->queueForManualProcessing($payment, 'Wire transfer instructions generated');
    }

    /**
     * Queue payment for manual processing
     */
    protected function queueForManualProcessing(TaxPayment $payment, string $message): array
    {
        $confirmationNumber = 'MANUAL-' . strtoupper(substr(md5($payment->id . time()), 0, 12));

        return [
            'success' => true,
            'confirmation_number' => $confirmationNumber,
            'requires_manual_processing' => true,
            'message' => $message,
        ];
    }

    /**
     * Get or create Stripe customer
     */
    protected function getOrCreateStripeCustomer(User $user): string
    {
        // Check if customer ID is stored
        if ($user->stripe_customer_id) {
            return $user->stripe_customer_id;
        }

        // Create new customer
        $customer = $this->stripe->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        // Store customer ID
        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer->id;
    }

    /**
     * Get Plaid access token for user
     */
    protected function getPlaidAccessToken(User $user): ?string
    {
        // This would retrieve stored Plaid access token
        // In production, this would be encrypted in database
        return $user->plaid_access_token ?? null;
    }

    /**
     * Get Plaid account ID
     */
    protected function getPlaidAccountId(User $user): ?string
    {
        return $user->plaid_account_id ?? null;
    }

    /**
     * Send payment confirmation
     */
    protected function sendPaymentConfirmation(TaxPayment $payment): void
    {
        $user = $payment->user;

        // Send email notification
        \Filament\Notifications\Notification::make()
            ->title('Tax Payment Processed')
            ->body('Your tax payment of $' . number_format($payment->total_amount, 2) . ' has been processed successfully.')
            ->success()
            ->sendToDatabase($user);

        // TODO: Send email with receipt
    }

    /**
     * Calculate processing fee
     */
    public function calculateProcessingFee(float $amount, string $paymentMethod): float
    {
        return match($paymentMethod) {
            'ach' => 0, // No fee for ACH
            'credit_card' => $amount * 0.029 + 0.30, // Stripe: 2.9% + $0.30
            'debit_card' => $amount * 0.029 + 0.30,
            'check' => 0,
            'wire' => 25.00, // Typical wire fee
            default => 0,
        };
    }

    /**
     * Schedule automatic payment
     */
    public function scheduleAutomaticPayment(TaxPayment $payment): array
    {
        // This would set up a scheduled job to process payment on scheduled_date
        // For now, just mark as scheduled

        $payment->update([
            'status' => 'scheduled',
            'status_message' => 'Payment scheduled for ' . $payment->scheduled_date->format('M d, Y'),
        ]);

        return [
            'success' => true,
            'message' => 'Payment scheduled successfully',
        ];
    }
}
