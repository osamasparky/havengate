<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactMessageResource extends Resource
{
    use \App\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $navigationGroup = 'Reservations';

    protected static ?string $navigationLabel = 'Enquiries';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return ($n = ContactMessage::whereNull('read_at')->count()) ? (string) $n : null;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\TextEntry::make('name'),
            Infolists\Components\TextEntry::make('email')->copyable()->url(fn ($record) => 'mailto:'.$record->email),
            Infolists\Components\TextEntry::make('phone')->placeholder(__('—')),
            Infolists\Components\TextEntry::make('subject')->placeholder(__('—')),
            Infolists\Components\TextEntry::make('message')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\IconColumn::make('read_at')->label('')->boolean()->trueIcon('heroicon-o-envelope-open')->falseIcon('heroicon-s-envelope')->falseColor('warning'),
                Tables\Columns\TextColumn::make('name')->searchable()->weight('semibold')->description(fn ($r) => $r->email),
                Tables\Columns\TextColumn::make('subject')->limit(40),
                Tables\Columns\TextColumn::make('message')->limit(60)->color('gray'),
                Tables\Columns\TextColumn::make('locale')->badge(),
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->after(fn (ContactMessage $r) => $r->read_at ?: $r->update(['read_at' => now()])),
                Tables\Actions\Action::make('reply')->icon('heroicon-o-arrow-uturn-left')->url(fn ($r) => 'mailto:'.$r->email.'?subject='.rawurlencode('Re: '.($r->subject ?: 'Heaven Gate Camp'))),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListContactMessages::route('/')];
    }
}
