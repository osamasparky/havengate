<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Enums\PaymentProvider;
use App\Exceptions\BookingException;
use App\Filament\Resources\BookingResource;
use App\Models\Accommodation;
use App\Models\Experience;
use App\Models\Unit;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PricingService;
use App\Support\StayRequest;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

/** Phone / walk-in / OTA bookings entered by staff. Uses the same service as the website. */
class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    public function getTitle(): string
    {
        return __('New booking');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('Stay'))->schema([
                Forms\Components\Select::make('accommodation_id')->label(__('Stay'))->required()->live()
                    ->options(Accommodation::active()->get()->mapWithKeys(fn ($a) => [$a->id => $a->name])),
                Forms\Components\DatePicker::make('check_in')->required()->native(false)->live()->minDate(today()->subDays(1)),
                Forms\Components\DatePicker::make('check_out')->required()->native(false)->live()->after('check_in'),
                Forms\Components\TextInput::make('adults')->numeric()->default(2)->minValue(1)->required()->live(),
                Forms\Components\TextInput::make('children')->numeric()->default(0)->minValue(0)->live(),
                Forms\Components\TextInput::make('rooms')->label(__('Units'))->numeric()->minValue(1)->maxValue(Accommodation::MAX_ROOMS_PER_BOOKING)->live()
                    ->placeholder(fn (Get $get) => ($n = static::roomsNeeded($get)) ? __('Auto').": {$n}" : __('Auto'))
                    ->helperText(__('Leave empty to use the fewest units that sleep the group.')),
                Forms\Components\Select::make('unit_id')->label(__('First unit (optional)'))->placeholder(__('Auto-assign free units'))
                    ->options(function (Get $get) {
                        if (! $get('accommodation_id') || ! $get('check_in') || ! $get('check_out')) {
                            return [];
                        }
                        $acc = Accommodation::find($get('accommodation_id'));

                        return app(AvailabilityService::class)
                            ->availableUnits($acc, Carbon::parse($get('check_in')), Carbon::parse($get('check_out')))
                            ->pluck('code', 'id');
                    }),
                Forms\Components\Toggle::make('with_pet'),
                Forms\Components\CheckboxList::make('extras')->label(__('Add-on experiences'))->columns(2)->columnSpanFull()
                    ->options(Experience::active()->where('is_addon', true)->get()->mapWithKeys(fn ($e) => [$e->id => $e->name.' · '.number_format($e->price)])),
                Forms\Components\Placeholder::make('quote')->label(__('Price'))->columnSpanFull()->content(function (Get $get) {
                    try {
                        if (! $get('accommodation_id') || ! $get('check_in') || ! $get('check_out')) {
                            return '—';
                        }
                        $stay = StayRequest::make($get('check_in'), $get('check_out'), (int) $get('adults'), (int) $get('children'), array_fill_keys((array) $get('extras'), (int) $get('adults') + (int) $get('children')), null, (bool) $get('with_pet'), (int) ($get('rooms') ?: static::roomsNeeded($get) ?: 1));
                        $q = app(PricingService::class)->quote(Accommodation::find($get('accommodation_id')), $stay, false);

                        return __(':nights nights · :rooms unit(s) · total :total (avg :avg / unit / night)', [
                            'nights' => $stay->nights, 'rooms' => $stay->rooms,
                            'total' => number_format($q->total, 2).' '.config('heavengate.currency'), 'avg' => number_format($q->averageNightly()),
                        ]);
                    } catch (\Throwable) {
                        return '—';
                    }
                }),
            ])->columns(3),

            Forms\Components\Section::make(__('Guest'))->schema([
                Forms\Components\TextInput::make('first_name')->required(),
                Forms\Components\TextInput::make('last_name')->required(),
                Forms\Components\TextInput::make('email')->email()->required()->helperText(__('Existing guests are matched by email.')),
                Forms\Components\TextInput::make('phone')->tel(),
                Forms\Components\TextInput::make('country')->maxLength(2)->placeholder(__('EG')),
                Forms\Components\Select::make('locale')->options(collect(config('heavengate.locales'))->map(fn ($l) => __($l['name'])))->default('en')->helperText(__('Language for guest emails.')),
            ])->columns(3),

            Forms\Components\Section::make(__('Details & payment'))->schema([
                Forms\Components\Select::make('source')->options(['phone' => __('Phone'), 'walk_in' => __('Walk-in'), 'ota' => __('OTA (Booking.com etc.)'), 'admin' => __('Other')])->default('phone')->required(),
                Forms\Components\TextInput::make('arrival_time'),
                Forms\Components\Toggle::make('confirm_now')->label(__('Confirm immediately'))->default(true)->helperText(__('Otherwise it stays pending until paid.')),
                Forms\Components\TextInput::make('payment_amount')->numeric()->label(__('Payment received now'))->prefix(config('heavengate.currency')),
                Forms\Components\Select::make('payment_provider')->options([
                    PaymentProvider::Cash->value => __('Cash'), PaymentProvider::InstaPay->value => __('InstaPay'), PaymentProvider::BankTransfer->value => __('Bank transfer'), PaymentProvider::Manual->value => __('Other'),
                ])->default(PaymentProvider::Cash->value),
                Forms\Components\Textarea::make('special_requests')->columnSpanFull(),
                Forms\Components\Textarea::make('internal_notes')->columnSpanFull(),
            ])->columns(3),
        ])->statePath('data');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $svc = app(BookingService::class);
        app()->setLocale($data['locale'] ?? 'en');

        try {
            $accommodation = Accommodation::findOrFail($data['accommodation_id']);
            $rooms = (int) ($data['rooms'] ?? 0) ?: ($accommodation->roomsNeeded((int) $data['adults'], (int) ($data['children'] ?? 0)) ?? 1);
            $stay = StayRequest::make($data['check_in'], $data['check_out'], (int) $data['adults'], (int) ($data['children'] ?? 0),
                array_fill_keys(array_map('intval', $data['extras'] ?? []), (int) $data['adults'] + (int) ($data['children'] ?? 0)), null, (bool) ($data['with_pet'] ?? false), $rooms);

            $booking = $svc->createHold($accommodation, $stay, [
                'first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => $data['email'],
                'phone' => $data['phone'] ?? null, 'country' => isset($data['country']) ? strtoupper($data['country']) : null,
            ], [
                'source' => $data['source'], 'unit_id' => $data['unit_id'] ?? null, 'hold_minutes' => 0,
                'arrival_time' => $data['arrival_time'] ?? null, 'special_requests' => $data['special_requests'] ?? null,
            ]);
        } catch (BookingException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
            app()->setLocale('en');
            throw new Halt;
        }

        $booking->update(['internal_notes' => $data['internal_notes'] ?? null]);

        if (! empty($data['payment_amount']) && (float) $data['payment_amount'] > 0) {
            $svc->recordManualPayment($booking, (float) $data['payment_amount'], PaymentProvider::from($data['payment_provider']), 'Recorded at booking');
        }
        if (! empty($data['confirm_now'])) {
            $svc->confirm($booking->fresh());
        }
        app()->setLocale('en');

        return $booking->fresh();
    }

    private static function roomsNeeded(Get $get): ?int
    {
        $acc = $get('accommodation_id') ? Accommodation::find($get('accommodation_id')) : null;

        return $acc?->roomsNeeded(max(1, (int) $get('adults')), max(0, (int) $get('children')));
    }

    protected function getRedirectUrl(): string
    {
        return BookingResource::getUrl('view', ['record' => $this->record]);
    }
}
