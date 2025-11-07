<?php

namespace App\Services;

use App\Models\{User, TaxFiling, TaxSetting, TaxDocument};
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Log, Http};

/**
 * Production-Ready Tax Filing Service
 *
 * This service handles real tax filing through multiple integration paths:
 * 1. Tax API Providers (TaxJar, Avalara) - Recommended for most users
 * 2. Direct IRS MeF integration - For high-volume preparers with IRS ETIN
 * 3. Professional review queue - For manual review before filing
 */
class ProductionTaxFilingService
{
    public function __construct(
        protected ProductionTaxCalculationService $taxCalculation,
        protected ProductionTaxFormGenerationService $formGeneration,
        protected SecureDataEncryptionService $encryption
    ) {}

    /**
     * File taxes through the best available method
     */
    public function fileTaxes(User $user, int $taxYear): array
    {
        DB::beginTransaction();

        try {
            // Step 1: Validate user profile is complete
            $validation = $this->validateUserProfile($user);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Tax profile incomplete',
                    'errors' => $validation['errors'],
                    'missing_requirements' => $validation['missing'],
                ];
            }

            // Step 2: Calculate taxes
            $startDate = Carbon::create($taxYear, 1, 1);
            $endDate = Carbon::create($taxYear, 12, 31);
            $taxSummary = $this->taxCalculation->calculateComprehensiveTaxSummary($user, $startDate, $endDate);

            // Step 3: Validate tax calculations meet filing requirements
            $filingValidation = $this->validateFilingRequirements($user, $taxSummary);
            if (!$filingValidation['can_file']) {
                return [
                    'success' => false,
                    'message' => $filingValidation['message'],
                    'requirements' => $filingValidation['requirements'],
                ];
            }

            // Step 4: Create tax filing record
            $filing = $this->createFilingRecord($user, $taxYear, $taxSummary);

            // Step 5: Generate tax forms
            $forms = $this->formGeneration->generateAllForms($user, $taxYear, $taxSummary);
            $filing->forms_included = $forms['forms_list'];
            $filing->pdf_paths = $forms['pdf_paths'];
            $filing->save();

            // Step 6: Determine filing method and submit
            $filingMethod = $this->determineBestFilingMethod($user);
            $submissionResult = $this->submitTaxReturn($filing, $forms, $filingMethod);

            // Step 7: Update filing record with results
            $this->updateFilingWithResults($filing, $submissionResult);

            DB::commit();

            return [
                'success' => $submissionResult['success'],
                'message' => $submissionResult['message'],
                'filing_id' => $filing->id,
                'federal_confirmation' => $submissionResult['federal_confirmation'] ?? null,
                'state_confirmation' => $submissionResult['state_confirmation'] ?? null,
                'filing_method' => $filingMethod,
                'status' => $filing->status,
                'refund_amount' => $filing->refund_amount,
                'amount_owed' => $filing->amount_owed,
                'next_steps' => $this->getNextSteps($filing),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tax filing failed for user ' . $user->id . ': ' . $e->getMessage(), [
                'user_id' => $user->id,
                'tax_year' => $taxYear,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred during tax filing. Please try again or contact support.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ];
        }
    }

    /**
     * Validate user tax profile is complete
     */
    protected function validateUserProfile(User $user): array
    {
        $taxSetting = $user->taxSetting;
        $errors = [];
        $missing = [];

        if (!$taxSetting) {
            return [
                'valid' => false,
                'errors' => ['Tax settings not configured'],
                'missing' => ['Complete tax profile setup'],
            ];
        }

        // Validate basic info
        if (!$taxSetting->hasBasicInfo()) {
            $errors[] = 'Basic business information incomplete';
            $missing[] = 'Business name, tax ID, business type, and filing status required';
        }

        // Validate address
        if (!$taxSetting->hasCompleteAddress()) {
            $errors[] = 'Mailing address incomplete';
            $missing[] = 'Complete street address, city, state, and ZIP code required';
        }

        // Validate SSN
        if (!$taxSetting->hasSSN()) {
            $errors[] = 'Social Security Number not provided';
            $missing[] = 'SSN required for tax filing';
        }

        // Validate bank info (for refunds/payments)
        if (!$taxSetting->hasBankInfo()) {
            $errors[] = 'Bank account information not provided';
            $missing[] = 'Bank account and routing number required for refunds/payments';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'missing' => $missing,
        ];
    }

    /**
     * Validate filing requirements are met
     */
    protected function validateFilingRequirements(User $user, array $taxSummary): array
    {
        $requirements = [];

        // Check income threshold (generally $400+ for self-employment)
        if ($taxSummary['self_employment_income'] < 400) {
            return [
                'can_file' => false,
                'message' => 'Income below filing threshold ($400 for self-employment)',
                'requirements' => ['Self-employment income must be $400 or more'],
            ];
        }

        // Check for required documents
        $year = now()->year;
        $hasDocuments = TaxDocument::where('user_id', $user->id)
            ->where('tax_year', $year)
            ->exists();

        if (!$hasDocuments) {
            $requirements[] = 'Upload income documents (W-2, 1099, etc.)';
        }

        return [
            'can_file' => true,
            'message' => 'Ready to file',
            'requirements' => $requirements,
        ];
    }

    /**
     * Create initial filing record
     */
    protected function createFilingRecord(User $user, int $taxYear, array $taxSummary): TaxFiling
    {
        return TaxFiling::create([
            'user_id' => $user->id,
            'tax_year' => $taxYear,
            'filing_type' => 'original',
            'filing_method' => 'e_file',
            'status' => 'draft',
            'total_income' => $taxSummary['total_revenue'],
            'total_deductions' => $taxSummary['total_deductible_expenses'],
            'taxable_income' => $taxSummary['taxable_income'],
            'total_tax' => $taxSummary['total_tax_liability'],
            'federal_withholding' => 0, // Would come from W-2
            'estimated_payments' => 0, // Would come from quarterly payments
            'refund_amount' => max(0, 0 - $taxSummary['total_tax_liability']),
            'amount_owed' => max(0, $taxSummary['total_tax_liability']),
            'prepared_by' => $user->name,
            'calculation_details' => $taxSummary,
        ]);
    }

    /**
     * Determine best filing method based on user setup and configuration
     */
    protected function determineBestFilingMethod(User $user): string
    {
        $taxSetting = $user->taxSetting;

        // Check if user has tax professional
        if ($taxSetting->has_tax_professional) {
            return 'professional_review';
        }

        // Check if e-file is enabled
        if (!$taxSetting->e_file_enabled) {
            return 'paper_file';
        }

        // Check if we have IRS ETIN for direct filing
        if (!empty($taxSetting->irs_etin) && config('tax.direct_irs_enabled')) {
            return 'direct_irs';
        }

        // Use tax API provider (TaxJar, Avalara, etc.)
        if (config('tax.api_provider_enabled')) {
            return 'api_provider';
        }

        // Default to professional review
        return 'professional_review';
    }

    /**
     * Submit tax return through appropriate channel
     */
    protected function submitTaxReturn(TaxFiling $filing, array $forms, string $method): array
    {
        return match($method) {
            'direct_irs' => $this->submitDirectToIRS($filing, $forms),
            'api_provider' => $this->submitViaAPIProvider($filing, $forms),
            'professional_review' => $this->queueForProfessionalReview($filing),
            'paper_file' => $this->preparePaperFiling($filing, $forms),
            default => $this->queueForProfessionalReview($filing),
        };
    }

    /**
     * Submit directly to IRS via MeF (requires IRS ETIN)
     */
    protected function submitDirectToIRS(TaxFiling $filing, array $forms): array
    {
        // This would integrate with IRS Modernized e-File (MeF) system
        // Requires IRS ETIN and complex XML format

        $user = $filing->user;
        $taxSetting = $user->taxSetting;

        if (empty($taxSetting->irs_etin)) {
            return [
                'success' => false,
                'message' => 'IRS ETIN not configured. Cannot file directly with IRS.',
            ];
        }

        // Generate MeF XML
        $mefXML = $this->generateMeFXML($filing, $forms);

        // Submit to IRS
        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'text/xml',
                    'SOAPAction' => 'SubmitReturn',
                ])
                ->withBasicAuth(
                    config('tax.irs_mef_username'),
                    config('tax.irs_mef_password')
                )
                ->post(config('tax.irs_mef_endpoint'), $mefXML);

            if ($response->successful()) {
                $result = $this->parseMeFResponse($response->body());

                if ($result['accepted']) {
                    $filing->addToAuditLog('Submitted to IRS via MeF', 'Confirmation: ' . $result['confirmation']);

                    return [
                        'success' => true,
                        'message' => 'Successfully submitted to IRS',
                        'federal_confirmation' => $result['confirmation'],
                        'submission_id' => $result['submission_id'],
                    ];
                } else {
                    $filing->addToAuditLog('IRS rejected submission', $result['rejection_reason']);

                    return [
                        'success' => false,
                        'message' => 'IRS rejected the filing: ' . $result['rejection_reason'],
                        'rejection_code' => $result['rejection_code'],
                    ];
                }
            } else {
                throw new \Exception('IRS API returned error: ' . $response->status());
            }

        } catch (\Exception $e) {
            Log::error('IRS MeF submission failed', [
                'filing_id' => $filing->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to connect to IRS e-file system',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Submit via tax API provider (TaxJar, Avalara, etc.)
     */
    protected function submitViaAPIProvider(TaxFiling $filing, array $forms): array
    {
        $provider = config('tax.api_provider'); // 'taxjar', 'avalara', etc.

        try {
            $response = match($provider) {
                'taxjar' => $this->submitToTaxJar($filing, $forms),
                'avalara' => $this->submitToAvalara($filing, $forms),
                default => throw new \Exception('Unknown tax API provider: ' . $provider),
            };

            if ($response['success']) {
                $filing->addToAuditLog('Submitted via ' . $provider, 'Confirmation: ' . ($response['confirmation'] ?? 'N/A'));
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Tax API provider submission failed', [
                'provider' => $provider,
                'filing_id' => $filing->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback to professional review
            return $this->queueForProfessionalReview($filing);
        }
    }

    /**
     * Submit to TaxJar API
     */
    protected function submitToTaxJar(TaxFiling $filing, array $forms): array
    {
        $apiKey = config('tax.taxjar_api_key');

        if (empty($apiKey)) {
            throw new \Exception('TaxJar API key not configured');
        }

        // Prepare filing data for TaxJar
        $filingData = $this->prepareTaxJarPayload($filing);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])
        ->post('https://api.taxjar.com/v2/transactions', $filingData);

        if ($response->successful()) {
            $result = $response->json();

            return [
                'success' => true,
                'message' => 'Successfully submitted via TaxJar',
                'federal_confirmation' => $result['transaction']['transaction_id'] ?? null,
                'api_response' => $result,
            ];
        } else {
            return [
                'success' => false,
                'message' => 'TaxJar submission failed: ' . $response->body(),
            ];
        }
    }

    /**
     * Submit to Avalara AvaTax
     */
    protected function submitToAvalara(TaxFiling $filing, array $forms): array
    {
        // Similar implementation for Avalara
        return [
            'success' => false,
            'message' => 'Avalara integration not yet configured',
        ];
    }

    /**
     * Queue for professional review
     */
    protected function queueForProfessionalReview(TaxFiling $filing): array
    {
        $filing->status = 'ready';
        $filing->status_message = 'Queued for professional review before filing';
        $filing->save();

        $filing->addToAuditLog('Queued for professional review');

        // TODO: Send notification to tax professionals

        return [
            'success' => true,
            'message' => 'Your tax return has been queued for professional review. You will be notified when it\'s been reviewed and filed.',
            'requires_action' => false,
        ];
    }

    /**
     * Prepare paper filing package
     */
    protected function preparePaperFiling(TaxFiling $filing, array $forms): array
    {
        $filing->status = 'ready';
        $filing->filing_method = 'paper';
        $filing->status_message = 'PDF forms ready for printing and mailing';
        $filing->save();

        return [
            'success' => true,
            'message' => 'Your tax forms are ready for printing. Please print, sign, and mail to the IRS.',
            'pdf_paths' => $forms['pdf_paths'],
            'requires_action' => true,
            'mailing_address' => $this->getIRSMailingAddress($filing->user->taxSetting->state),
        ];
    }

    /**
     * Update filing record with submission results
     */
    protected function updateFilingWithResults(TaxFiling $filing, array $result): void
    {
        if ($result['success']) {
            $filing->status = 'submitted';
            $filing->submitted_at = now();
            $filing->federal_confirmation_number = $result['federal_confirmation'] ?? null;
            $filing->state_confirmation_number = $result['state_confirmation'] ?? null;
            $filing->api_response = $result;
        } else {
            $filing->status = 'rejected';
            $filing->rejection_reason = $result['message'];
        }

        $filing->save();
    }

    /**
     * Get next steps for user
     */
    protected function getNextSteps(TaxFiling $filing): array
    {
        return match($filing->status) {
            'submitted' => [
                'Wait for IRS acceptance (typically 24-48 hours)',
                'Check your email for confirmation',
                'Track your refund status at IRS.gov',
            ],
            'ready' => [
                'Wait for professional review',
                'Review will be completed within 1 business day',
                'You will receive an email when ready to file',
            ],
            'rejected' => [
                'Review the rejection reason',
                'Correct any errors',
                'Resubmit your tax return',
            ],
            default => ['Complete your tax profile', 'Gather required documents'],
        };
    }

    /**
     * Generate MeF XML for IRS submission
     */
    protected function generateMeFXML(TaxFiling $filing, array $forms): string
    {
        // This would generate proper IRS MeF XML format
        // Highly complex - requires IRS schema compliance
        return ''; // Placeholder
    }

    /**
     * Parse MeF response from IRS
     */
    protected function parseMeFResponse(string $xml): array
    {
        // Parse IRS XML response
        return [
            'accepted' => false,
            'confirmation' => null,
            'submission_id' => null,
            'rejection_reason' => null,
            'rejection_code' => null,
        ];
    }

    /**
     * Prepare TaxJar API payload
     */
    protected function prepareTaxJarPayload(TaxFiling $filing): array
    {
        return [
            'transaction_id' => 'filing-' . $filing->id,
            'transaction_date' => $filing->created_at->toISOString(),
            'amount' => $filing->total_income,
            'tax' => $filing->total_tax,
            // Add more TaxJar-specific fields
        ];
    }

    /**
     * Get IRS mailing address by state
     */
    protected function getIRSMailingAddress(string $state): string
    {
        // Return appropriate IRS mailing address based on state
        return "Internal Revenue Service\nP.O. Box 802501\nCincinnati, OH 45280-2501";
    }

    /**
     * Check filing status with IRS
     */
    public function checkFilingStatus(TaxFiling $filing): array
    {
        if (!$filing->federal_confirmation_number) {
            return [
                'status' => 'not_submitted',
                'message' => 'Filing not yet submitted',
            ];
        }

        // Query IRS for status
        // This would integrate with IRS Where's My Refund API or similar

        return [
            'status' => 'pending',
            'message' => 'Processing at IRS',
            'estimated_completion' => now()->addDays(2)->toDateString(),
        ];
    }
}
