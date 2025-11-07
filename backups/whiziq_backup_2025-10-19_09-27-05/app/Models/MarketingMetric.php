<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'platform',
        'channel',
        'followers',
        'engagement',
        'reach',
        'awareness',
        'leads',
        'conversions',
        'retention_count',
        'cost_per_click',
        'cost_per_conversion',
        'ad_spend',
        'clicks',
        'conversion_rate',
        'engagement_rate',
        'customer_lifetime_value',
        'customer_acquisition_cost',
        'clv_cac_ratio',
        'impressions',
        'roi',
    ];

    protected $casts = [
        'date' => 'date',
        'followers' => 'integer',
        'engagement' => 'integer',
        'reach' => 'integer',
        'awareness' => 'integer',
        'leads' => 'integer',
        'conversions' => 'integer',
        'retention_count' => 'integer',
        'cost_per_click' => 'decimal:2',
        'cost_per_conversion' => 'decimal:2',
        'ad_spend' => 'decimal:2',
        'clicks' => 'integer',
        'conversion_rate' => 'decimal:2',
        'engagement_rate' => 'decimal:2',
        'customer_lifetime_value' => 'decimal:2',
        'customer_acquisition_cost' => 'decimal:2',
        'clv_cac_ratio' => 'decimal:2',
        'impressions' => 'integer',
        'roi' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPlatformIconAttribute(): string
    {
        return match($this->platform) {
            'facebook' => 'fab-facebook',
            'instagram' => 'fab-instagram',
            'linkedin' => 'fab-linkedin',
            'twitter' => 'fab-twitter',
            default => 'fas-share-alt',
        };
    }

    public function getChannelNameAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->channel));
    }

    public function getLeadConversionRateAttribute(): float
    {
        if ($this->awareness == 0) {
            return 0;
        }
        return round(($this->leads / $this->awareness) * 100, 2);
    }

    public function getCustomerConversionRateAttribute(): float
    {
        if ($this->leads == 0) {
            return 0;
        }
        return round(($this->conversions / $this->leads) * 100, 2);
    }

    public function getRetentionRateAttribute(): float
    {
        if ($this->conversions == 0) {
            return 0;
        }
        return round(($this->retention_count / $this->conversions) * 100, 2);
    }

    public function getCLVCACHealthAttribute(): string
    {
        if ($this->clv_cac_ratio >= 3) {
            return 'excellent';
        } elseif ($this->clv_cac_ratio >= 2) {
            return 'good';
        } elseif ($this->clv_cac_ratio >= 1) {
            return 'acceptable';
        } else {
            return 'poor';
        }
    }

    public function getROIHealthAttribute(): string
    {
        if ($this->roi >= 200) {
            return 'excellent';
        } elseif ($this->roi >= 100) {
            return 'good';
        } elseif ($this->roi >= 50) {
            return 'acceptable';
        } else {
            return 'poor';
        }
    }
}
