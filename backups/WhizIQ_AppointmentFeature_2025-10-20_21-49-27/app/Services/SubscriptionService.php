<?php

namespace App\Services;

use App\Constants\PaymentProviderConstants;
use App\Constants\PlanType;
use App\Constants\SubscriptionStatus;
use App\Constants\SubscriptionType;
use App\Events\Subscription\InvoicePaymentFailed;
use App\Events\Subscription\Subscribed;
use App\Events\Subscription\SubscribedOffline;
use App\Events\Subscription\SubscriptionCancelled;
use App\Events\Subscription\SubscriptionRenewed;
use App\Exceptions\CouldNotCreateLocalSubscriptionException;
use App\Exceptions\SubscriptionCreationNotAllowedException;
use App\Models\PaymentProvider;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserSubscriptionTrial;
use App\Services\PaymentProviders\PaymentProviderInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionService
{
    public function __construct(
        private CalculationService $calculationService,
        private PlanService $planService,
    ) {}

    public function canCreateSubscription(int $userId): bool
    {
        if (config('app.multiple_subscriptions_enabled')) {
            return true;
        }

        $notDeadSubscriptions = $this->findAllSubscriptionsThatAreNotDead($userId);

        return count($notDeadSubscriptions) === 0;
    }

    public function create(
        string $planSlug,
        int $userId,
        ?PaymentProvider $paymentProvider = null,
        ?string $paymentProviderSubscriptionId = null,
        bool $localSubscription = false,
        ?Carbon $endsAt = null,
    ): Subscription {
        $plan = Plan::where('slug', $planSlug)->where('is_active', true)->firstOrFail();

        if (! $this->canCreateSubscription($userId)) {
            throw new SubscriptionCreationNotAllowedException(__('You already have subscription.'));
        }

        $newSubscription = null;
        DB::transaction(function () use ($plan, $userId, &$newSubscription, $paymentProvider, $paymentProviderSubscriptionId, $localSubscription, $endsAt) {
            $this->deleteAllNewSubscriptions($userId);

            $planPrice = $this->calculationService->getPlanPrice($plan);

            $subscriptionAttributes = [
                'uuid' => (string) Str::uuid(),
                'user_id' => $userId,
                'plan_id' => $plan->id,
                'price' => $planPrice->price,
                'currency_id' => $planPrice->currency_id,
                'status' => SubscriptionStatus::NEW->value,
                'interval_id' => $plan->interval_id,
                'interval_count' => $plan->interval_count,
                'price_type' => $planPrice->type,
                'price_tiers' => $planPrice->tiers,
                'price_per_unit' => $planPrice->price_per_unit,
                'type' => SubscriptionType::PAYMENT_PROVIDER_MANAGED,
            ];

            if ($paymentProvider) {
                $subscriptionAttributes['payment_provider_id'] = $paymentProvider->id;
            }

            if ($paymentProviderSubscriptionId) {
                $subscriptionAttributes['payment_provider_subscription_id'] = $paymentProviderSubscriptionId;
            }

            if ($localSubscription) {
                $subscriptionAttributes['type'] = SubscriptionType::LOCALLY_MANAGED;

                $endDate = $endsAt ?? ($plan->has_trial ? now()->addDays($this->calculateSubscriptionTrialDays($plan)) : null);
                if ($endDate === null) {
                    throw new CouldNotCreateLocalSubscriptionException('Could not determine local subscription end date');
                }

                $subscriptionAttributes['ends_at'] = $endDate;

                if ($plan->has_trial) {
                    $subscriptionAttributes['trial_ends_at'] = $endDate;
                }

                $user = User::find($userId);
                if ($this->shouldUserVerifyPhoneNumberForTrial($user)) {
                    $subscriptionAttributes['status'] = SubscriptionStatus::PENDING_USER_VERIFICATION->value;
                } else {
                    $subscriptionAttributes['status'] = SubscriptionStatus::ACTIVE->value;
                }
            }

            $newSubscription = Subscription::create($subscriptionAttributes);

            if ($localSubscription) {
                // if it's a local subscription, dispatch Subscribed event.
                // Payment provider subscriptions events are dispatched by payment provider strategy
                Subscribed::dispatch($newSubscription);
            }

            $this->updateUserSubscriptionTrials($newSubscription->id);
        });

        return $newSubscription;
    }

    public function shouldUserVerifyPhoneNumberForTrial(User $user): bool
    {
        return config('app.trial_without_payment.sms_verification_enabled') && ! $user->isPhoneNumberVerified();
    }

    public function findAllSubscriptionsThatAreNotDead(int $userId): array
    {
        return Subscription::where('user_id', $userId)
            ->where(function ($query) {
                $query->where('status', SubscriptionStatus::ACTIVE->value)
                    ->orWhere('status', SubscriptionStatus::PENDING->value)
                    ->orWhere('status', SubscriptionStatus::PAUSED->value)
                    ->orWhere('status', SubscriptionStatus::PAST_DUE->value);
            })
            ->get()
            ->toArray();
    }

    public function setAsPending(int $subscriptionId): void
    {
        // make it all in one statement to avoid overwriting webhook status updates
        Subscription::where('id', $subscriptionId)
            ->where('status', SubscriptionStatus::NEW->value)
            ->where('type', SubscriptionType::PAYMENT_PROVIDER_MANAGED)
            ->update([
                'status' => SubscriptionStatus::PENDING->value,
            ]);
    }

    public function deleteAllNewSubscriptions(int $userId): void
    {
        Subscription::where('user_id', $userId)
            ->where('status', SubscriptionStatus::NEW->value)
            ->delete();
    }

    public function findActiveUserSubscription(int $userId): ?Subscription
    {
        return Subscription::where('user_id', $userId)
            ->where('status', '=', SubscriptionStatus::ACTIVE->value)
            ->first();
    }

    public function findActiveUserSubscriptions(User $user): Collection
    {
        return Subscription::where('user_id', $user->id)
            ->where('status', '=', SubscriptionStatus::ACTIVE->value)
            ->where('ends_at', '>', now())
            ->get();
    }

    public function findActiveUserSubscriptionProducts(User $user): Collection
    {
        return $this->findActiveUserSubscriptions($user)
            ->map(function (Subscription $subscription) {
                return $subscription->plan->product;
            });
    }

    public function findActiveUserSubscriptionWithPlanType(int $userId, PlanType $planType): ?Subscription
    {
        return Subscription::where('user_id', $userId)
            ->where('status', '=', SubscriptionStatus::ACTIVE->value)
            ->whereHas('plan', function ($query) use ($planType) {
                $query->where('type', $planType->value);
            })->first();
    }

    public function findActiveByUserAndSubscriptionUuid(int $userId, string $subscriptionUuid): ?Subscription
    {
        return Subscription::where('user_id', $userId)
            ->where('uuid', $subscriptionUuid)
            ->where('status', '=', SubscriptionStatus::ACTIVE->value)
            ->first();
    }

    public function findNewByPlanSlugAndUser(string $planSlug, int $userId): ?Subscription
    {
        $plan = Plan::where('slug', $planSlug)->where('is_active', true)->firstOrFail();

        return Subscription::where('user_id', $userId)
            ->where('plan_id', $plan->id)
            ->where('status', SubscriptionStatus::NEW->value)
            ->first();
    }

    public function findByUuidOrFail(string $uuid): Subscription
    {
        return Subscription::where('uuid', $uuid)->firstOrFail();
    }

    public function findByUuidAndUserIdOrFail(string $uuid, int $userId): Subscription
    {
        return Subscription::where('uuid', $uuid)
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    public function isLocalSubscription(Subscription $subscription): bool
    {
        return $subscription->type === SubscriptionType::LOCALLY_MANAGED;
    }

    public function isIncompleteSubscription(Subscription $subscription): bool
    {
        return $this->isLocalSubscription($subscription) && $subscription->paymentProvider === null;
    }

    public function shouldSkipTrial(Subscription $subscription)
    {
        if ($this->isLocalSubscription($subscription) && $subscription->plan->has_trial) {
            return true;
        }

        return ! $this->canUserHaveSubscriptionTrial($subscription->user);
    }

    public function findById(int $id): ?Subscription
    {
        return Subscription::find($id);
    }

    public function findByPaymentProviderId(PaymentProvider $paymentProvider, string $paymentProviderSubscriptionId): ?Subscription
    {
        return Subscription::where('payment_provider_id', $paymentProvider->id)
            ->where('payment_provider_subscription_id', $paymentProviderSubscriptionId)
            ->first();
    }

    public function updateSubscription(
        Subscription $subscription,
        array $data
    ): Subscription {
        $oldStatus = $subscription->status;
        $newStatus = $data['status'] ?? $oldStatus;
        $oldEndsAt = $subscription->ends_at;
        $newEndsAt = $data['ends_at'] ?? $oldEndsAt;
        $subscription->update($data);

        $this->updateUserSubscriptionTrials($subscription->id);

        $this->handleDispatchingEvents(
            $oldStatus,
            $newStatus,
            $oldEndsAt,
            $newEndsAt,
            $subscription
        );

        return $subscription;
    }

    private function handleDispatchingEvents(
        string $oldStatus,
        string|SubscriptionStatus $newStatus,
        Carbon|string|null $oldEndsAt,
        Carbon|string|null $newEndsAt,
        Subscription $subscription
    ): void {
        $newStatus = $newStatus instanceof SubscriptionStatus ? $newStatus->value : $newStatus;

        if ($oldStatus !== $newStatus) {
            switch ($newStatus) {
                case SubscriptionStatus::ACTIVE->value:
                    Subscribed::dispatch($subscription);
                    break;
                case SubscriptionStatus::CANCELED->value:
                    SubscriptionCancelled::dispatch($subscription);
                    break;
            }
        }

        // if $oldEndsAt is string, convert it to Carbon
        if (is_string($oldEndsAt)) {
            $oldEndsAt = Carbon::parse($oldEndsAt);
        }

        // if $newEndsAt is string, convert it to Carbon
        if (is_string($newEndsAt)) {
            $newEndsAt = Carbon::parse($newEndsAt);
        }

        // if $newEndsAt > $oldEndsAt, then subscription is renewed
        if ($newEndsAt && $oldEndsAt && $newEndsAt->greaterThan($oldEndsAt)) {
            SubscriptionRenewed::dispatch($subscription, $oldEndsAt, $newEndsAt);
        }

        if ($newStatus == SubscriptionStatus::PENDING->value && $this->isLocalSubscription($subscription) && $subscription->paymentProvider->slug === PaymentProviderConstants::OFFLINE_SLUG) {
            // If the subscription is pending and it's an offline order, dispatch SubscribedOffline event (you can use this to let the user know that they need to pay offline)
            SubscribedOffline::dispatch($subscription);
        }
    }

    public function handleInvoicePaymentFailed(Subscription $subscription)
    {
        InvoicePaymentFailed::dispatch($subscription);
    }

    public function calculateSubscriptionTrialDays(Plan $plan): int
    {
        if (! $plan->has_trial) {
            return 0;
        }

        $interval = $plan->trialInterval()->firstOrFail();
        $intervalCount = $plan->trial_interval_count;

        $now = Carbon::now();

        return intval(round(abs(now()->add($interval->date_identifier, $intervalCount)->diffInDays($now))));
    }

    public function changePlan(Subscription $subscription, PaymentProviderInterface $paymentProviderStrategy, string $newPlanSlug, bool $isProrated = false): bool
    {
        if ($subscription->plan->slug === $newPlanSlug) {
            return false;
        }

        if (! $this->planService->isPlanChangeable($subscription->plan)) {
            return false;
        }

        $newPlan = $this->planService->getActivePlanBySlug($newPlanSlug);

        if (! $newPlan) {
            return false;
        }

        $changeResult = $paymentProviderStrategy->changePlan($subscription, $newPlan, $isProrated);

        if ($changeResult) {
            Subscribed::dispatch($subscription);

            return true;
        }

        return false;
    }

    public function canAddDiscount(Subscription $subscription)
    {
        return $subscription->type === SubscriptionType::PAYMENT_PROVIDER_MANAGED &&
            ($subscription->status === SubscriptionStatus::ACTIVE->value ||
            $subscription->status === SubscriptionStatus::PAST_DUE->value)
            && $subscription->price > 0
            && $subscription->discounts()->count() === 0  // only one discount per subscription for now
            && $subscription->paymentProvider->slug !== PaymentProviderConstants::LEMON_SQUEEZY_SLUG; // LemonSqueezy does not support discounts for active subscriptions
    }

    public function cancelSubscription(
        Subscription $subscription,
        PaymentProviderInterface $paymentProviderStrategy,
        string $reason,
        ?string $additionalInfo = null

    ): bool {
        $result = $paymentProviderStrategy->cancelSubscription($subscription);

        if ($result) {
            $this->updateSubscription($subscription, [
                'is_canceled_at_end_of_cycle' => true,
                'cancellation_reason' => $reason,
                'cancellation_additional_info' => $additionalInfo,
            ]);
        }

        return $result;
    }

    public function discardSubscriptionCancellation(Subscription $subscription, PaymentProviderInterface $paymentProviderStrategy): bool
    {
        $result = $paymentProviderStrategy->discardSubscriptionCancellation($subscription);

        if ($result) {
            $this->updateSubscription($subscription, [
                'is_canceled_at_end_of_cycle' => false,
                'cancellation_reason' => null,
                'cancellation_additional_info' => null,
            ]);
        }

        return $result;
    }

    public function isUserSubscribed(?User $user, ?string $productSlug = null): bool
    {
        if (! $user) {
            return false;
        }

        $subscriptions = $user->subscriptions()
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->where('ends_at', '>', Carbon::now())
            ->get();

        if ($productSlug) {
            $subscriptions = $subscriptions->filter(function (Subscription $subscription) use ($productSlug) {
                return $subscription->plan->product->slug === $productSlug;
            });
        }

        return $subscriptions->count() > 0;
    }

    public function isUserTrialing(?User $user, ?string $productSlug = null): bool
    {
        if (! $user) {
            return false;
        }

        $subscriptions = $user->subscriptions()
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->where('trial_ends_at', '>', Carbon::now())
            ->get();

        if ($productSlug) {
            $subscriptions = $subscriptions->filter(function (Subscription $subscription) use ($productSlug) {
                return $subscription->plan->product->slug === $productSlug;
            });
        }

        return $subscriptions->count() > 0;
    }

    public function getUserSubscriptionProductMetadata(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $subscriptions = $user->subscriptions()
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->where('ends_at', '>', Carbon::now())
            ->get();

        if ($subscriptions->count() === 0) {
            // if there is no active subscriptions, return metadata of default product
            $defaultProduct = Product::where('is_default', true)->first();

            if (! $defaultProduct) {
                return [];
            }

            return $defaultProduct->metadata ?? [];
        }

        // if there is 1 subscription, return metadata of its product
        if ($subscriptions->count() === 1) {
            return $subscriptions->first()->plan->product->metadata ?? [];
        }

        // if there are multiple subscriptions, return array of product-slug => metadata
        return $subscriptions->mapWithKeys(function (Subscription $subscription) {
            return [$subscription->plan->product->slug => $subscription->plan->product->metadata ?? []];
        })->toArray();
    }

    public function canEditSubscriptionPaymentDetails(Subscription $subscription)
    {
        return $subscription->type === SubscriptionType::PAYMENT_PROVIDER_MANAGED &&
            ($subscription->status === SubscriptionStatus::ACTIVE->value || $subscription->status === SubscriptionStatus::PAST_DUE->value);
    }

    public function canCancelSubscription(Subscription $subscription)
    {
        return $subscription->type === SubscriptionType::PAYMENT_PROVIDER_MANAGED &&
            ! $subscription->is_canceled_at_end_of_cycle &&
            $subscription->status === SubscriptionStatus::ACTIVE->value;
    }

    public function canDiscardSubscriptionCancellation(Subscription $subscription)
    {
        return $subscription->type === SubscriptionType::PAYMENT_PROVIDER_MANAGED &&
            $subscription->is_canceled_at_end_of_cycle &&
            $subscription->status === SubscriptionStatus::ACTIVE->value;
    }

    public function canChangeSubscriptionPlan(Subscription $subscription)
    {
        return $subscription->type === SubscriptionType::PAYMENT_PROVIDER_MANAGED &&
            $this->planService->isPlanChangeable($subscription->plan) &&
            $subscription->status === SubscriptionStatus::ACTIVE->value;
    }

    public function getLocalSubscriptionExpiringIn(int $days)
    {
        return Subscription::where('type', SubscriptionType::LOCALLY_MANAGED)
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->where('payment_provider_id', null)
            // on that exact day
            ->whereDate('ends_at', Carbon::now()->addDays($days)->toDateString())
            ->get();
    }

    public function canEndSubscription(Subscription $subscription)
    {
        return $this->isLocalSubscription($subscription) &&
            $subscription->status === SubscriptionStatus::ACTIVE->value;
    }

    public function canUpdateSubscription(Subscription $subscription)
    {
        return $this->isLocalSubscription($subscription);
    }

    public function endSubscription(Subscription $subscription): bool
    {
        if (! $this->isLocalSubscription($subscription)) {
            return false;
        }

        $this->updateSubscription($subscription, [
            'status' => SubscriptionStatus::INACTIVE->value,
            'ends_at' => now(),
            'trial_ends_at' => now(),
        ]);

        return true;
    }

    public function cleanupLocalSubscriptionStatuses()
    {
        $subscriptions = Subscription::where('type', SubscriptionType::LOCALLY_MANAGED)
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->where('ends_at', '<', now())
            ->get();

        $subscriptions->each(function (Subscription $subscription) {
            $this->updateSubscription($subscription, [
                'status' => SubscriptionStatus::INACTIVE->value,
            ]);
        });
    }

    public function updateUserSubscriptionTrials(int $subscriptionId)
    {
        $subscription = Subscription::where('id', $subscriptionId)
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->whereNotNull('trial_ends_at')
            ->first();

        if (! $subscription) {
            return;
        }

        $user = $subscription->user;

        // if user already has a trial for this subscription, do not create another one
        UserSubscriptionTrial::query()->firstOrCreate([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
        ], [
            'trial_ends_at' => $subscription->trial_ends_at,
        ]);
    }

    public function getUserSubscriptionTrialCount(int $userId): int
    {
        return UserSubscriptionTrial::where('user_id', $userId)->count();
    }

    public function canUserHaveSubscriptionTrial(?User $user): bool
    {
        if (! $user) {
            return true;
        }

        if (! config('app.limit_user_trials.enabled')) {
            return true;
        }

        if ($this->getUserSubscriptionTrialCount($user->id) >= config('app.limit_user_trials.max_count')) {
            return false;
        }

        return true;
    }

    public function activateSubscriptionsPendingUserVerification(User $user)
    {
        $subscriptions = Subscription::where('user_id', $user->id)
            ->where('status', SubscriptionStatus::PENDING_USER_VERIFICATION->value)
            ->get();

        $subscriptions->each(function (Subscription $subscription) {
            $this->updateSubscription($subscription, [
                'status' => SubscriptionStatus::ACTIVE->value,
            ]);
        });
    }

    public function subscriptionRequiresUserVerification(Subscription $subscription): bool
    {
        return $subscription->status === SubscriptionStatus::PENDING_USER_VERIFICATION->value &&
            $this->shouldUserVerifyPhoneNumberForTrial($subscription->user);
    }
}
