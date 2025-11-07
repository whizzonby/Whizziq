<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update old country codes to ISO 3166-1 alpha-2 format
        $countryMappings = [
            'USA' => 'US',
            'CAN' => 'CA',
            'GBR' => 'GB',
            'AUS' => 'AU',
            'DEU' => 'DE',
            'FRA' => 'FR',
        ];

        foreach ($countryMappings as $oldCode => $newCode) {
            DB::table('invoice_clients')
                ->where('country', $oldCode)
                ->update(['country' => $newCode]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to old country codes
        $countryMappings = [
            'US' => 'USA',
            'CA' => 'CAN',
            'GB' => 'GBR',
            'AU' => 'AUS',
            'DE' => 'DEU',
            'FR' => 'FRA',
        ];

        foreach ($countryMappings as $newCode => $oldCode) {
            DB::table('invoice_clients')
                ->where('country', $newCode)
                ->update(['country' => $oldCode]);
        }
    }
};