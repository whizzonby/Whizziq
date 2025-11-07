<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Dompdf\Dompdf;
use Dompdf\Options;

class RealPDFGeneratorService
{
    /**
     * Generate real PDF tax forms
     */
    public function generateTaxFormPDF(User $user, array $formData, string $formType, int $year): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        $html = $this->generateFormHTML($formData, $formType, $year);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = "tax_form_{$user->id}_{$year}_{$formType}_" . now()->format('YmdHis') . '.pdf';
        $path = "tax_forms/{$filename}";
        
        $pdfContent = $dompdf->output();
        Storage::put($path, $pdfContent);
        
        return $path;
    }

    /**
     * Generate HTML for tax forms
     */
    protected function generateFormHTML(array $formData, string $formType, int $year): string
    {
        switch ($formType) {
            case 'schedule_c':
                return $this->generateScheduleCHTML($formData, $year);
            case 'form_1040':
                return $this->generateForm1040HTML($formData, $year);
            case 'schedule_se':
                return $this->generateScheduleSEHTML($formData, $year);
            case '1099':
                return $this->generate1099HTML($formData, $year);
            default:
                return $this->generateGenericFormHTML($formData, $year);
        }
    }

    /**
     * Generate Schedule C HTML
     */
    protected function generateScheduleCHTML(array $formData, int $year): string
    {
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Schedule C - {$year}</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                .form-title { font-weight: bold; font-size: 14px; text-align: center; margin-bottom: 20px; }
                .form-section { margin-bottom: 15px; }
                .form-line { margin-bottom: 8px; }
                .form-label { display: inline-block; width: 200px; }
                .form-value { display: inline-block; width: 100px; text-align: right; border-bottom: 1px solid black; }
                .form-section-title { font-weight: bold; margin-bottom: 10px; }
                .form-grid { display: table; width: 100%; }
                .form-row { display: table-row; }
                .form-cell { display: table-cell; padding: 2px; }
            </style>
        </head>
        <body>
            <div class='form-title'>Schedule C - Profit or Loss from Business (Form 1040)</div>
            <div class='form-title'>Department of the Treasury - Internal Revenue Service</div>
            <div class='form-title'>Tax Year: {$year}</div>
            
            <div class='form-section'>
                <div class='form-section-title'>Part I - Income</div>
                <div class='form-line'>
                    <span class='form-label'>Gross receipts or sales:</span>
                    <span class='form-value'>$" . number_format($formData['part_1_income']['gross_receipts'], 2) . "</span>
                </div>
                <div class='form-line'>
                    <span class='form-label'>Returns and allowances:</span>
                    <span class='form-value'>$" . number_format($formData['part_1_income']['returns_allowances'], 2) . "</span>
                </div>
                <div class='form-line'>
                    <span class='form-label'>Net receipts:</span>
                    <span class='form-value'>$" . number_format($formData['part_1_income']['net_receipts'], 2) . "</span>
                </div>
            </div>
            
            <div class='form-section'>
                <div class='form-section-title'>Part II - Expenses</div>
                <div class='form-grid'>
                    <div class='form-row'>
                        <div class='form-cell'>Advertising:</div>
                        <div class='form-cell'>$" . number_format($formData['part_2_expenses']['advertising'], 2) . "</div>
                    </div>
                    <div class='form-row'>
                        <div class='form-cell'>Car and truck expenses:</div>
                        <div class='form-cell'>$" . number_format($formData['part_2_expenses']['car_truck_expenses'], 2) . "</div>
                    </div>
                    <div class='form-row'>
                        <div class='form-cell'>Commissions and fees:</div>
                        <div class='form-cell'>$" . number_format($formData['part_2_expenses']['commissions_fees'], 2) . "</div>
                    </div>
                    <div class='form-row'>
                        <div class='form-cell'>Contract labor:</div>
                        <div class='form-cell'>$" . number_format($formData['part_2_expenses']['contract_labor'], 2) . "</div>
                    </div>
                    <div class='form-row'>
                        <div class='form-cell'>Depreciation:</div>
                        <div class='form-cell'>$" . number_format($formData['part_2_expenses']['depreciation'], 2) . "</div>
                    </div>
                    <div class='form-row'>
                        <div class='form-cell'>Insurance:</div>
                        <div class='form-cell'>$" . number_format($formData['part_2_expenses']['insurance'], 2) . "</div>
                    </div>
                    <div class='form-row'>
                        <div class='form-cell'>Interest:</div>
                        <div class='form-cell'>$" . number_format($formData['part_2_expenses']['interest'], 2) . "</div>
                    </div>
                    <div class='form-row'>
                        <div class='form-cell'>Legal and professional services:</div>
                        <div class='form-cell'>$" . number_format($formData['part_2_expenses']['legal_professional_services'], 2) . "</div>
                    </div>
                    <div class='form-row'>
                        <div class='form-cell'>Office expenses:</div>
                        <div class='form-cell'>$" . number_format($formData['part_2_expenses']['office_expenses'], 2) . "</div>
                    </div>
                    <div class='form-row'>
                        <div class='form-cell'>Rent or lease:</div>
                        <div class='form-cell'>$" . number_format($formData['part_2_expenses']['rent_lease'], 2) . "</div>
                    </div>
                    <div class='form-row'>
                        <div class='form-cell'>Repairs and maintenance:</div>
                        <div class='form-cell'>$" . number_format($formData['part_2_expenses']['repairs_maintenance'], 2) . "</div>
                    </div>
                    <div class='form-row'>
                        <div class='form-cell'>Supplies:</div>
                        <div class='form-cell'>$" . number_format($formData['part_2_expenses']['supplies'], 2) . "</div>
                    </div>
                    <div class='form-row'>
                        <div class='form-cell'>Taxes and licenses:</div>
                        <div class='form-cell'>$" . number_format($formData['part_2_expenses']['taxes_licenses'], 2) . "</div>
                    </div>
                    <div class='form-row'>
                        <div class='form-cell'>Travel:</div>
                        <div class='form-cell'>$" . number_format($formData['part_2_expenses']['travel'], 2) . "</div>
                    </div>
                    <div class='form-row'>
                        <div class='form-cell'>Meals:</div>
                        <div class='form-cell'>$" . number_format($formData['part_2_expenses']['meals'], 2) . "</div>
                    </div>
                    <div class='form-row'>
                        <div class='form-cell'>Utilities:</div>
                        <div class='form-cell'>$" . number_format($formData['part_2_expenses']['utilities'], 2) . "</div>
                    </div>
                    <div class='form-row'>
                        <div class='form-cell'>Other expenses:</div>
                        <div class='form-cell'>$" . number_format($formData['part_2_expenses']['other_expenses'], 2) . "</div>
                    </div>
                </div>
            </div>
            
            <div class='form-section'>
                <div class='form-section-title'>Net Profit or Loss</div>
                <div class='form-line'>
                    <span class='form-label'>Net profit or (loss):</span>
                    <span class='form-value'>$" . number_format($formData['net_profit'], 2) . "</span>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }

    /**
     * Generate Form 1040 HTML
     */
    protected function generateForm1040HTML(array $formData, int $year): string
    {
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Form 1040 - {$year}</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                .form-title { font-weight: bold; font-size: 14px; text-align: center; margin-bottom: 20px; }
                .form-section { margin-bottom: 15px; }
                .form-line { margin-bottom: 8px; }
                .form-label { display: inline-block; width: 200px; }
                .form-value { display: inline-block; width: 100px; text-align: right; border-bottom: 1px solid black; }
            </style>
        </head>
        <body>
            <div class='form-title'>Form 1040 - U.S. Individual Income Tax Return</div>
            <div class='form-title'>Department of the Treasury - Internal Revenue Service</div>
            <div class='form-title'>Tax Year: {$year}</div>
            
            <div class='form-section'>
                <div class='form-line'>
                    <span class='form-label'>Name:</span>
                    <span class='form-value'>{$formData['personal_info']['first_name']}</span>
                </div>
                <div class='form-line'>
                    <span class='form-label'>Filing Status:</span>
                    <span class='form-value'>{$formData['filing_status']}</span>
                </div>
            </div>
            
            <div class='form-section'>
                <div class='form-section-title'>Income</div>
                <div class='form-line'>
                    <span class='form-label'>Wages, salaries, tips, etc.:</span>
                    <span class='form-value'>$" . number_format($formData['income']['wages_salaries'], 2) . "</span>
                </div>
                <div class='form-line'>
                    <span class='form-label'>Business income:</span>
                    <span class='form-value'>$" . number_format($formData['income']['business_income'], 2) . "</span>
                </div>
                <div class='form-line'>
                    <span class='form-label'>Total income:</span>
                    <span class='form-value'>$" . number_format($formData['income']['total_income'], 2) . "</span>
                </div>
            </div>
            
            <div class='form-section'>
                <div class='form-section-title'>Tax and Credits</div>
                <div class='form-line'>
                    <span class='form-label'>Amount owed:</span>
                    <span class='form-value'>$" . number_format($formData['refund_or_amount_owed']['amount_owed'], 2) . "</span>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }

    /**
     * Generate Schedule SE HTML
     */
    protected function generateScheduleSEHTML(array $formData, int $year): string
    {
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Schedule SE - {$year}</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                .form-title { font-weight: bold; font-size: 14px; text-align: center; margin-bottom: 20px; }
                .form-section { margin-bottom: 15px; }
                .form-line { margin-bottom: 8px; }
                .form-label { display: inline-block; width: 200px; }
                .form-value { display: inline-block; width: 100px; text-align: right; border-bottom: 1px solid black; }
            </style>
        </head>
        <body>
            <div class='form-title'>Schedule SE - Self-Employment Tax</div>
            <div class='form-title'>Department of the Treasury - Internal Revenue Service</div>
            <div class='form-title'>Tax Year: {$year}</div>
            
            <div class='form-section'>
                <div class='form-line'>
                    <span class='form-label'>Net earnings from self-employment:</span>
                    <span class='form-value'>$" . number_format($formData['net_earnings'], 2) . "</span>
                </div>
                <div class='form-line'>
                    <span class='form-label'>Self-employment tax:</span>
                    <span class='form-value'>$" . number_format($formData['self_employment_tax'], 2) . "</span>
                </div>
                <div class='form-line'>
                    <span class='form-label'>Deduction for half of SE tax:</span>
                    <span class='form-value'>$" . number_format($formData['deduction_for_half'], 2) . "</span>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }

    /**
     * Generate 1099 HTML
     */
    protected function generate1099HTML(array $formData, int $year): string
    {
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Form 1099-NEC - {$year}</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                .form-title { font-weight: bold; font-size: 14px; text-align: center; margin-bottom: 20px; }
                .form-section { margin-bottom: 15px; }
                .form-line { margin-bottom: 8px; }
                .form-label { display: inline-block; width: 200px; }
                .form-value { display: inline-block; width: 100px; text-align: right; border-bottom: 1px solid black; }
            </style>
        </head>
        <body>
            <div class='form-title'>Form 1099-NEC - Nonemployee Compensation</div>
            <div class='form-title'>Department of the Treasury - Internal Revenue Service</div>
            <div class='form-title'>Tax Year: {$year}</div>
            
            <div class='form-section'>
                <div class='form-line'>
                    <span class='form-label'>Contractor Name:</span>
                    <span class='form-value'>{$formData['contractor_name']}</span>
                </div>
                <div class='form-line'>
                    <span class='form-label'>Contractor TIN:</span>
                    <span class='form-value'>{$formData['contractor_tin']}</span>
                </div>
                <div class='form-line'>
                    <span class='form-label'>Amount Paid:</span>
                    <span class='form-value'>$" . number_format($formData['amount_paid'], 2) . "</span>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }

    /**
     * Generate generic form HTML
     */
    protected function generateGenericFormHTML(array $formData, int $year): string
    {
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>{$formData['form_name']} - {$year}</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                .form-title { font-weight: bold; font-size: 14px; text-align: center; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class='form-title'>{$formData['form_name']}</div>
            <div class='form-title'>Tax Year: {$year}</div>
            <p>Form data: " . json_encode($formData, JSON_PRETTY_PRINT) . "</p>
        </body>
        </html>";
        
        return $html;
    }
}
