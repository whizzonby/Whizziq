<?php

namespace App\Providers;

use App\Models\User;
use App\Services\OrderService;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Blade::if('subscribed', function (?string $productSlug = null) {
            /** @var User $user */
            $user = auth()->user();

            /** @var SubscriptionService $subscriptionService */
            $subscriptionService = app(SubscriptionService::class);

            return $subscriptionService->isUserSubscribed($user, $productSlug);
        });

        Blade::if('notsubscribed', function (?string $productSlug = null) {
            /** @var User $user */
            $user = auth()->user();

            /** @var SubscriptionService $subscriptionService */
            $subscriptionService = app(SubscriptionService::class);

            return ! $subscriptionService->isUserSubscribed($user, $productSlug);
        });

        Blade::if('trialing', function (?string $productSlug = null) {
            /** @var User $user */
            $user = auth()->user();

            /** @var SubscriptionService $subscriptionService */
            $subscriptionService = app(SubscriptionService::class);

            return $subscriptionService->isUserTrialing($user, $productSlug);
        });

        Blade::if('purchased', function (?string $productSlug = null) {
            /** @var User $user */
            $user = auth()->user();

            /** @var OrderService $orderService */
            $orderService = app(OrderService::class);

            return $orderService->hasUserOrdered($user, $productSlug);
        });
    }
}
