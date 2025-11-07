@props([
    'subscription' => null,
    'buyRoute' => 'subscription.change-plan',
    'plan',
])

@inject('planService', 'App\Services\PlanService')

@php
    $price = $planService->getPlanPrice($plan);
@endphp


<div class="relative flex flex-col justify-between p-8 transition-shadow duration-300 border rounded-2xl shadow-sm sm:items-center hover:shadow border-deep-purple-accent-400">
    @if($plan->product && $plan->product->is_popular)
        <div class="absolute inset-x-0 top-0 flex justify-center -mt-3">
            <div class="inline-block px-3 py-1 text-xs font-medium tracking-wider text-white uppercase rounded bg-primary-500">
                {{__('Most Popular')}}
            </div>
        </div>
    @endif

    <div class="text-center">
        <div class="text-lg font-semibold">{{ $plan->product ? __($plan->product->name) : __('Plan') }}</div>
        <div class="flex items-center justify-center mt-2">
            @if($price && isset($price->price) && $price->price > 0 && $price->currency && $price->currency->code)
                <div class="mr-1 text-4xl font-bold">@money($price->price, $price->currency->code)</div>
                @if($plan->interval && isset($plan->interval->name))
                    <div class="text-sm">/ {{$plan->interval_count > 1 ? $plan->interval_count : '' }} {{ __($plan->interval->name) }}</div>
                @else
                    <div class="text-sm">/ {{ __('One-time') }}</div>
                @endif
            @else
                <div class="text-sm text-gray-500">{{ __('Price not available') }}</div>
            @endif

            @if($price && $price->currency && isset($price->currency->code) && $price->type === \App\Constants\PlanPriceType::USAGE_BASED_PER_UNIT->value && isset($price->price_per_unit) && $plan->meter && isset($plan->meter->name))
                <div class="text-sm mt-2">
                    + @money($price->price_per_unit, $price->currency->code) / {{ __($plan->meter->name) }}
                </div>
            @elseif($price && $price->currency && isset($price->currency->code) && ($price->type === \App\Constants\PlanPriceType::USAGE_BASED_TIERED_GRADUATED->value
                    || $price->type === \App\Constants\PlanPriceType::USAGE_BASED_TIERED_VOLUME->value) && $price->tiers && $plan->meter && isset($plan->meter->name))
                <div class="text-xs mt-2">
                    @php $start = 0; $startingPhrase = __('From'); @endphp
                    @foreach($price->tiers as $tier)
                        <div class="mt-2 text-sm">
                            <span class="font-semibold"> {{$startingPhrase}} {{ $start }} - {{ $tier[\App\Constants\PlanPriceTierConstants::UNTIL_UNIT] ?? 0 }} {{ $plan->meter && isset($plan->meter->name) ? __(strtolower(str()->plural($plan->meter->name))) : __('units') }} </span>
                            → <span class="">@money($tier[\App\Constants\PlanPriceTierConstants::PER_UNIT] ?? 0, $price->currency->code ?? 'USD') / {{ $plan->meter && isset($plan->meter->name) ? __($plan->meter->name) : __('unit') }} </span>
                            @if (isset($tier[\App\Constants\PlanPriceTierConstants::FLAT_FEE]) && $tier[\App\Constants\PlanPriceTierConstants::FLAT_FEE] > 0)
                                + @money($tier['flat_fee'], $price->currency->code ?? 'USD')
                            @endif
                        </div>
                        @php $start = intval($tier[\App\Constants\PlanPriceTierConstants::UNTIL_UNIT] ?? 0) + 1; @endphp

                        @if($price->type === \App\Constants\PlanPriceType::USAGE_BASED_TIERED_GRADUATED->value)
                            @php $startingPhrase = __('Next'); @endphp
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
        <div class="mt-3 space-y-3">
            <ul>
                @if($plan->product && $plan->product->features)
                    @foreach($plan->product->features as $feature)
                        <li>{{ is_array($feature) ? ($feature['feature'] ?? '') : $feature }}</li>
                    @endforeach
                @endif
            </ul>
        </div>
    </div>
    <div class="w-full">
        <a class="btn btn-block bg-primary-500 dark:bg-primary-500 text-white px-6 mt-6 border-0 hover:bg-primary-500/90"
           {{$subscription !== null && $subscription->plan_id === $plan->id ? 'disabled' : ''}}
           href="{{ route($buyRoute, ['planSlug' => $plan->slug, 'subscriptionUuid' => $subscription?->uuid]) }}">
            {{__('Buy')}} {{ $plan->product ? $plan->product->name : __('Plan') }}
        </a>
        <p class="max-w-xs mt-6 text-xs text-gray-600 sm:text-sm sm:text-center sm:max-w-sm sm:mx-auto dark:text-zinc-400">
            {{ $plan->product && $plan->product->description ? $plan->product->description : '' }}
        </p>
    </div>
</div>
