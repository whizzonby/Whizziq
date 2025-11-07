<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'symbol',
    ];

    /**
     * Get all currencies formatted for select dropdown
     * Format: ['USD' => 'USD ($)', 'EUR' => 'EUR (€)', ...]
     */
    public static function getSelectOptions(): array
    {
        return Cache::remember('currency_select_options', 3600, function () {
            return static::query()
                ->orderBy('code', 'asc')
                ->get()
                ->mapWithKeys(function ($currency) {
                    $label = $currency->code;
                    if ($currency->symbol) {
                        $label .= ' (' . $currency->symbol . ')';
                    }
                    return [$currency->code => $label];
                })
                ->toArray();
        });
    }

    /**
     * Get popular currencies formatted for select dropdown
     * Returns top currencies commonly used in business
     */
    public static function getPopularCurrencies(): array
    {
        $popularCodes = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CNY', 'INR', 'CHF', 'SGD'];

        return Cache::remember('currency_popular_options', 3600, function () use ($popularCodes) {
            return static::query()
                ->whereIn('code', $popularCodes)
                ->orderByRaw("FIELD(code, '" . implode("','", $popularCodes) . "')")
                ->get()
                ->mapWithKeys(function ($currency) {
                    $label = $currency->code;
                    if ($currency->symbol) {
                        $label .= ' (' . $currency->symbol . ')';
                    }
                    return [$currency->code => $label];
                })
                ->toArray();
        });
    }

    /**
     * Clear currency cache when model is updated
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            Cache::forget('currency_select_options');
            Cache::forget('currency_popular_options');
        });

        static::deleted(function () {
            Cache::forget('currency_select_options');
            Cache::forget('currency_popular_options');
        });
    }
}
