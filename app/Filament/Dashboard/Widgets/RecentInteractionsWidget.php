<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\ContactInteraction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class RecentInteractionsWidget extends BaseWidget
{
    protected static ?int $sort = 8;

    /**
     * Hide on main dashboard - only show on CRM Dashboard
     */
    public static function canView(): bool
    {
        $livewire = \Livewire\Livewire::current();
        if ($livewire instanceof \Filament\Pages\Dashboard) {
            return false;
        }
        return true;
    }

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ContactInteraction::query()
                    ->with(['contact', 'deal'])
                    ->where('user_id', Auth::id())
                    ->orderBy('interaction_date', 'desc')
                    ->limit(15)
            )
            ->heading('Recent Interactions')
            ->columns([
                Tables\Columns\TextColumn::make('interaction_date')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->since(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'call',
                        'info' => 'email',
                        'success' => 'meeting',
                        'warning' => 'demo',
                        'secondary' => ['note', 'task', 'other'],
                    ]),

                Tables\Columns\TextColumn::make('contact.name')
                    ->label('Contact')
                    ->searchable()
                    ->url(fn (ContactInteraction $record) => route('filament.dashboard.resources.contacts.edit', $record->contact)),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Subject')
                    ->searchable()
                    ->limit(40)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(fn (ContactInteraction $record) => $record->description),

                Tables\Columns\TextColumn::make('duration_formatted')
                    ->label('Duration')
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('outcome')
                    ->colors([
                        'success' => 'positive',
                        'warning' => 'neutral',
                        'danger' => 'negative',
                        'info' => 'follow_up_needed',
                    ])
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deal.title')
                    ->label('Related Deal')
                    ->limit(30)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated([15]);
    }
}
