<?php

namespace App\Http\Controllers;

use App\Services\CalculationService;
use App\Services\PaymentProviders\PaymentService;
use App\Services\PlanService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private PlanService $planService,
        private SubscriptionService $subscriptionService,
        private PaymentService $paymentService,
        private CalculationService $calculationService,
    ) {}

    public function changePlan(string $subscriptionUuid, string $newPlanSlug, Request $request)
    {
        $user = auth()->user();

        $userSubscription = $this->subscriptionService->findActiveByUserAndSubscriptionUuid($user->id, $subscriptionUuid);

        if (! $userSubscription) {
            return redirect()->back()->with('error', __('You do not have an active subscription.'));
        }

        if ($userSubscription->plan->slug === $newPlanSlug) {
            return redirect()->back()->with('error', __('You are already subscribed to this plan.'));
        }

        $paymentProvider = $userSubscription->paymentProvider()->first();

        if (! $paymentProvider) {
            return redirect()->back()->with('error', __('Error finding payment provider.'));
        }

        $paymentProviderStrategy = $this->paymentService->getPaymentProviderBySlug(
            $paymentProvider->slug
        );

        $newPlan = $this->planService->getActivePlanBySlug($newPlanSlug);

        $isProrated = config('app.payment.proration_enabled', true);

        $totals = $this->calculationService->calculateNewPlanTotals(
            $user,
            $newPlanSlug,
            $isProrated,
        );

        if ($request->isMethod('post')) {
            $result = $this->subscriptionService->changePlan($userSubscription, $paymentProviderStrategy, $newPlanSlug, $isProrated);

            if ($result) {
                return redirect()->route('subscription.change-plan.thank-you');
            } else {
                return redirect()->route('home')->with('error', __('Error changing plan.'));
            }
        }

        return view('subscription.change', [
            'subscription' => $userSubscription,
            'newPlan' => $newPlan,
            'isProrated' => $isProrated,
            'user' => $user,
            'totals' => $totals,
        ]);
    }

    public function success()
    {
        return view('subscription.change-thank-you');
    }
}
