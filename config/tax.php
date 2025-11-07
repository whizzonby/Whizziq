<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tax API Provider
    |--------------------------------------------------------------------------
    |
    | Choose your tax filing API provider:
    | - 'taxjar' - TaxJar API (recommended for most users)
    | - 'avalara' - Avalara AvaTax (enterprise-grade)
    | - 'none' - Professional review queue only
    |
    */

    'api_provider' => env('TAX_API_PROVIDER', 'taxjar'),
    'api_provider_enabled' => env('TAX_API_PROVIDER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | TaxJar Configuration
    |--------------------------------------------------------------------------
    |
    | TaxJar provides easy-to-use tax filing APIs. Quick setup, no IRS ETIN required.
    | Sign up at: https://www.taxjar.com
    |
    */

    'taxjar' => [
        'api_key' => env('TAXJAR_API_KEY'),
        'api_url' => env('TAXJAR_API_URL', 'https://api.taxjar.com/v2'),
        'enabled' => env('TAXJAR_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Avalara AvaTax Configuration
    |--------------------------------------------------------------------------
    |
    | Avalara AvaTax for enterprise-grade tax compliance.
    | Sign up at: https://www.avalara.com
    |
    */

    'avalara' => [
        'account_id' => env('AVALARA_ACCOUNT_ID'),
        'license_key' => env('AVALARA_LICENSE_KEY'),
        'api_url' => env('AVALARA_API_URL', 'https://rest.avatax.com'),
        'enabled' => env('AVALARA_ENABLED', false),
        'environment' => env('AVALARA_ENVIRONMENT', 'sandbox'), // sandbox or production
    ],

    /*
    |--------------------------------------------------------------------------
    | Direct IRS MeF Integration
    |--------------------------------------------------------------------------
    |
    | Direct submission to IRS via Modernized e-File (MeF).
    | Requires IRS ETIN and MeF certification.
    | Apply for ETIN: https://www.irs.gov/e-file-providers
    |
    */

    'irs_mef' => [
        'enabled' => env('IRS_MEF_ENABLED', false),
        'endpoint' => env('IRS_MEF_ENDPOINT', 'https://testbed.irs.gov/mef'),
        'username' => env('IRS_MEF_USERNAME'),
        'password' => env('IRS_MEF_PASSWORD'),
        'etin' => env('IRS_MEF_ETIN'),
        'environment' => env('IRS_MEF_ENVIRONMENT', 'testbed'), // testbed or production
    ],

    'direct_irs_enabled' => env('IRS_MEF_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | State Tax APIs
    |--------------------------------------------------------------------------
    |
    | State-specific tax authority APIs. Most users should use TaxJar or
    | Avalara which handle multi-state filing automatically.
    |
    */

    'state_apis' => [
        'CA' => env('CA_TAX_API_URL'),
        'NY' => env('NY_TAX_API_URL'),
        'TX' => env('TX_TAX_API_URL'),
        'FL' => env('FL_TAX_API_URL'),
    ],

    'state_api_keys' => [
        'CA' => env('CA_TAX_API_KEY'),
        'NY' => env('NY_TAX_API_KEY'),
        'TX' => env('TX_TAX_API_KEY'),
        'FL' => env('FL_TAX_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF Generation
    |--------------------------------------------------------------------------
    |
    | Configuration for tax form PDF generation
    |
    */

    'pdf' => [
        'storage_disk' => env('TAX_PDF_STORAGE_DISK', 'local'),
        'storage_path' => env('TAX_PDF_STORAGE_PATH', 'tax_forms'),
        'retention_years' => env('TAX_PDF_RETENTION_YEARS', 7), // IRS requires 3-7 years
    ],

    /*
    |--------------------------------------------------------------------------
    | Filing Configuration
    |--------------------------------------------------------------------------
    |
    | General tax filing settings
    |
    */

    'filing' => [
        'max_retries' => env('TAX_FILING_MAX_RETRIES', 3),
        'retry_delay' => env('TAX_FILING_RETRY_DELAY', 5), // seconds
        'timeout' => env('TAX_FILING_TIMEOUT', 60), // seconds
        'require_professional_review' => env('TAX_REQUIRE_PROFESSIONAL_REVIEW', false),
        'auto_file_enabled' => env('TAX_AUTO_FILE_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Tax filing notification settings
    |
    */

    'notifications' => [
        'email_enabled' => env('TAX_EMAIL_NOTIFICATIONS', true),
        'sms_enabled' => env('TAX_SMS_NOTIFICATIONS', false),
        'webhook_enabled' => env('TAX_WEBHOOK_NOTIFICATIONS', false),
        'webhook_url' => env('TAX_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Year Settings
    |--------------------------------------------------------------------------
    |
    | Current tax year and important deadlines
    |
    */

    'current_tax_year' => env('TAX_CURRENT_YEAR', now()->year),
    'filing_season_start' => env('TAX_FILING_SEASON_START', now()->year . '-01-15'),
    'filing_deadline' => env('TAX_FILING_DEADLINE', now()->year . '-04-15'),

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable/disable specific tax features
    |
    */

    'features' => [
        'document_ocr' => env('TAX_FEATURE_OCR', true),
        'auto_categorization' => env('TAX_FEATURE_AUTO_CATEGORIZE', true),
        'quarterly_estimates' => env('TAX_FEATURE_QUARTERLY_ESTIMATES', true),
        'state_filing' => env('TAX_FEATURE_STATE_FILING', true),
        'payment_plans' => env('TAX_FEATURE_PAYMENT_PLANS', true),
        'amendments' => env('TAX_FEATURE_AMENDMENTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance
    |--------------------------------------------------------------------------
    |
    | Compliance and security settings
    |
    */

    'compliance' => [
        'require_ssn' => env('TAX_REQUIRE_SSN', true),
        'require_bank_account' => env('TAX_REQUIRE_BANK_ACCOUNT', true),
        'require_address_verification' => env('TAX_REQUIRE_ADDRESS_VERIFICATION', false),
        'enable_audit_log' => env('TAX_ENABLE_AUDIT_LOG', true),
    ],
];
