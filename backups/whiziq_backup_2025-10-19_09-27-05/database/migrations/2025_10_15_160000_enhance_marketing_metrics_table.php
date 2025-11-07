<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('marketing_metrics', function (Blueprint $table) {
            // Conversion Funnel Stages
            $table->integer('awareness')->default(0)->after('reach');
            $table->integer('leads')->default(0)->after('awareness');
            $table->integer('conversions')->default(0)->after('leads');
            $table->integer('retention_count')->default(0)->after('conversions');

            // Channel-specific data
            $table->string('channel')->default('organic')->after('platform'); // facebook, google, linkedin, organic
            $table->decimal('cost_per_click', 10, 2)->nullable()->after('channel');
            $table->decimal('cost_per_conversion', 10, 2)->nullable()->after('cost_per_click');
            $table->decimal('ad_spend', 10, 2)->default(0)->after('cost_per_conversion');

            // Enhanced engagement metrics
            $table->integer('clicks')->default(0)->after('engagement');
            $table->decimal('conversion_rate', 8, 2)->default(0)->after('clicks'); // percentage
            $table->decimal('engagement_rate', 8, 2)->default(0)->after('conversion_rate'); // percentage

            // CLV and CAC
            $table->decimal('customer_lifetime_value', 10, 2)->default(0)->after('engagement_rate');
            $table->decimal('customer_acquisition_cost', 10, 2)->default(0)->after('customer_lifetime_value');
            $table->decimal('clv_cac_ratio', 8, 2)->default(0)->after('customer_acquisition_cost');

            // Additional metrics
            $table->integer('impressions')->default(0)->after('clv_cac_ratio');
            $table->decimal('roi', 8, 2)->default(0)->after('impressions'); // Return on Investment percentage

            // Add index for channel queries
            $table->index(['user_id', 'channel', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketing_metrics', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'channel', 'date']);

            $table->dropColumn([
                'awareness',
                'leads',
                'conversions',
                'retention_count',
                'channel',
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
            ]);
        });
    }
};
