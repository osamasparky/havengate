<?php

namespace App\Filament\Resources\BookingResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Audit trail: every status change, payment and note. Staff can add notes. */
class EventsRelationManager extends RelationManager
{
    use \App\Filament\Concerns\TranslatesRelationLabels;

    protected static ?string $modelLabel = 'note';

    protected static string $relationship = 'events';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('History');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('type')->default('note'),
            Forms\Components\Textarea::make('message')->label(__('Note'))->required()->rows(3)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->dateTime('j M H:i'),
                Tables\Columns\TextColumn::make('type')->badge()->color(fn ($state) => match ($state) {
                    'confirmed', 'payment_received' => 'success', 'cancelled', 'expired', 'payment_failed', 'needs_attention' => 'danger',
                    'note' => 'info', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('message')->wrap(),
                Tables\Columns\TextColumn::make('user.name')->label(__('By'))->placeholder(__('System')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label(__('Add note'))
                    ->mutateFormDataUsing(fn (array $data) => $data + ['user_id' => auth()->id()]),
            ]);
    }
}
