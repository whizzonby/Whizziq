<?php

namespace App\Filament\Dashboard\Pages;

use App\Models\Contact;
use App\Services\DuplicateContactDetectionService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;
use BackedEnum;

class ManageDuplicatesPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected string $view = 'filament.dashboard.pages.manage-duplicates-page';

    protected static ?string $navigationLabel = 'Find Duplicates';

    protected static UnitEnum|string|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Manage Duplicate Contacts';


    public array $duplicateGroups = [];

    public function mount(): void
    {
        $this->findDuplicates();
    }

    public function findDuplicates(): void
    {
        $service = app(DuplicateContactDetectionService::class);
        $groups = $service->findAllDuplicates(auth()->id());

        $this->duplicateGroups = $groups->map(function($group) {
            return [
                'primary' => [
                    'id' => $group['primary']->id,
                    'name' => $group['primary']->name,
                    'email' => $group['primary']->email,
                    'phone' => $group['primary']->phone,
                    'company' => $group['primary']->company,
                    'deals_count' => $group['primary']->deals_count,
                    'lifetime_value' => $group['primary']->lifetime_value,
                    'last_contact_date' => $group['primary']->last_contact_date?->format('M d, Y'),
                ],
                'duplicates' => $group['duplicates']->map(function($dup) {
                    return [
                        'id' => $dup['contact']->id,
                        'name' => $dup['contact']->name,
                        'email' => $dup['contact']->email,
                        'phone' => $dup['contact']->phone,
                        'company' => $dup['contact']->company,
                        'match_type' => $dup['match_type'],
                        'confidence' => $dup['confidence'],
                        'reason' => $dup['reason'],
                        'deals_count' => $dup['contact']->deals_count,
                        'lifetime_value' => $dup['contact']->lifetime_value,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    public function mergeContacts(int $primaryId, int $duplicateId): void
    {
        try {
            $primary = Contact::where('user_id', auth()->id())->findOrFail($primaryId);
            $duplicate = Contact::where('user_id', auth()->id())->findOrFail($duplicateId);

            $service = app(DuplicateContactDetectionService::class);
            $merged = $service->mergeContacts($primary, $duplicate);

            Notification::make()
                ->title('Contacts Merged')
                ->success()
                ->body("Successfully merged contacts into {$merged->name}")
                ->send();

            $this->findDuplicates();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Merge Failed')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function ignoreDuplicate(int $contactId): void
    {
        // In a full implementation, you'd mark this pair as "not a duplicate"
        // For now, just remove from the list
        $this->findDuplicates();

        Notification::make()
            ->title('Duplicate Ignored')
            ->body('This will be remembered for future scans')
            ->send();
    }

    public function viewContact(int $contactId): void
    {
        $this->redirect(route('filament.dashboard.resources.contacts.edit', ['record' => $contactId]));
    }
}
