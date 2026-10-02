<?php

namespace App\Filament\Resources\BookingResource\RelationManagers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\BookingService;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    use \App\Filament\Concerns\TranslatesRelationLabels;

    protected static ?string $modelLabel = 'payment';

    protected static string $relationship = 'payments';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->dateTime('j M Y H:i'),
                Tables\Columns\TextColumn::make('provider')->badge(),
                Tables\Columns\TextColumn::make('method')->formatStateUsing(fn ($state) => __(ucfirst(str_replace('_', ' ', (string) $state))))->placeholder(__('—')),
                Tables\Columns\TextColumn::make('amount')->money(config('heavengate.currency')),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('reference')->fontFamily('mono')->copyable()->toggleable(),
                Tables\Columns\TextColumn::make('provider_reference')->label(__('Gateway ref'))->copyable()->toggleable(),
                Tables\Columns\TextColumn::make('voucher')->placeholder(__('—'))->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('recorder.name')->label(__('By'))->placeholder(__('Gateway')),
                Tables\Columns\TextColumn::make('notes')->limit(30)->toggleable(),
            ])
            ->actions([
                Tables\Actions\Action::make('refunded')->label(__('Mark refunded'))->icon('heroicon-o-arrow-uturn-left')->color('danger')
                    ->visible(fn (Payment $p) => $p->status === PaymentStatus::Paid)
                    ->requiresConfirmation()->modalDescription(__('Record that this payment was refunded (do the actual refund in EasyKash / cash first).'))
                    ->action(function (Payment $p) {
                        $p->update(['status' => PaymentStatus::Refunded]);
                        app(BookingService::class)->refreshPaymentTotals($p->booking);
                        $p->booking->log('refunded', number_format($p->amount, 2));
                    }),
            ]);
    }
}
