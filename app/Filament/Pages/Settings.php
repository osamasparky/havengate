<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/** Business details, stay rules, money and cancellation policy. Stored in `settings`. */
class Settings extends Page implements HasForms
{
    use \App\Filament\Concerns\TranslatesPageLabels;

    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Booking & business settings';

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    private const TRANSLATABLE = ['address', 'offline_payment_instructions'];

    /** Values for keys added after the initial seed (match the setting() fallbacks in code). */
    public const DEFAULTS = [
        'reviews_enabled' => true, 'reviews_require_booking' => true, 'reviews_auto_approve' => false,
        'pay_at_property_enabled' => true, 'pay_at_property_auto_confirm' => true,
    ];

    public static function canAccess(): bool
    {
        return auth()->user()?->canManage() ?? false;
    }

    public function mount(): void
    {
        $all = Setting::all_cached() + self::DEFAULTS;
        foreach (self::TRANSLATABLE as $k) {
            foreach (array_keys(config('heavengate.locales')) as $l) {
                $all["{$k}_{$l}"] = $all[$k][$l] ?? null;
            }
        }
        $this->form->fill($all);
    }

    public function form(Form $form): Form
    {
        $locales = config('heavengate.locales');
        $tr = fn (string $key, string $label, bool $textarea = false) => Forms\Components\Fieldset::make($label)->schema(
            collect($locales)->map(fn ($l, $code) => ($textarea ? Forms\Components\Textarea::make("{$key}_{$code}")->rows(3) : Forms\Components\TextInput::make("{$key}_{$code}"))
                ->label(__($l['name']))->extraInputAttributes(['dir' => $l['dir']]))->values()->all()
        )->columns(1);

        return $form->statePath('data')->schema([
            Forms\Components\Tabs::make()->tabs([
                Forms\Components\Tabs\Tab::make(__('Contact'))->icon('heroicon-o-phone')->schema([
                    Forms\Components\TextInput::make('contact_phone'),
                    Forms\Components\TextInput::make('contact_whatsapp')->helperText(__('International format, e.g. +2010…')),
                    Forms\Components\TextInput::make('contact_email')->email(),
                    Forms\Components\TextInput::make('notification_email')->email()->helperText(__('Receives new booking alerts.')),
                    Forms\Components\TextInput::make('instagram')->url()->columnSpanFull(),
                    $tr('address', 'Address')->columnSpanFull(),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make(__('Stay rules'))->icon('heroicon-o-home')->schema([
                    Forms\Components\TextInput::make('check_in_time')->placeholder(__('14:00')),
                    Forms\Components\TextInput::make('check_out_time')->placeholder(__('11:00')),
                    Forms\Components\TextInput::make('children_max_age')->numeric(),
                    Forms\Components\TextInput::make('min_lead_days')->numeric()->helperText(__('0 = same-day bookings allowed')),
                    Forms\Components\TextInput::make('max_nights')->numeric(),
                    Forms\Components\CheckboxList::make('weekend_nights')->options([0 => __('Sun'), 1 => __('Mon'), 2 => __('Tue'), 3 => __('Wed'), 4 => __('Thu'), 5 => __('Fri'), 6 => __('Sat')])->columns(7)
                        ->helperText(__('Nights charged at the weekend price.'))->columnSpanFull(),
                    Forms\Components\Toggle::make('pets_allowed'),
                    Forms\Components\TextInput::make('pet_fee_per_night')->numeric()->prefix(config('heavengate.currency')),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make(__('Payments & taxes'))->icon('heroicon-o-banknotes')->schema([
                    Forms\Components\Toggle::make('online_payment_enabled')->label(__('Online payment (EasyKash)')),
                    Forms\Components\Toggle::make('offline_payment_enabled')->label(__('Bank transfer / InstaPay')),
                    Forms\Components\Toggle::make('pay_at_property_enabled')->label(__('Pay at property (on arrival)'))->live()
                        ->helperText(__('Guests book without paying online and pay the full amount at the camp.')),
                    Forms\Components\Toggle::make('pay_at_property_auto_confirm')->label(__('Confirm pay-at-property bookings automatically'))
                        ->helperText(__('Off = the booking waits in Reservations until staff confirm it ("Confirm without payment") or cancel it. The unit stays held meanwhile.'))
                        ->visible(fn (Forms\Get $get) => (bool) $get('pay_at_property_enabled')),
                    Forms\Components\TextInput::make('deposit_percent')->numeric()->minValue(1)->maxValue(100)->suffix('%')->helperText(__('100 = full payment online; 30 = 30% deposit, balance at the camp.')),
                    Forms\Components\TextInput::make('offline_hold_hours')->numeric()->suffix(__('h')),
                    Forms\Components\TextInput::make('service_charge_percent')->numeric()->suffix('%'),
                    Forms\Components\TextInput::make('vat_percent')->numeric()->suffix('%'),
                    $tr('offline_payment_instructions', 'Transfer instructions shown to guests', true)->columnSpanFull(),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make(__('Cancellation policy'))->icon('heroicon-o-shield-check')->schema([
                    Forms\Components\TextInput::make('free_cancellation_days')->numeric()->suffix(__('days before arrival'))->helperText(__('Full refund at or before this.')),
                    Forms\Components\TextInput::make('late_cancellation_days')->numeric()->suffix(__('days before arrival'))->helperText(__('Partial refund between this and the free window.')),
                    Forms\Components\TextInput::make('late_cancellation_refund_percent')->numeric()->suffix('%'),
                    Forms\Components\Placeholder::make('note')->content(__('Remember to update the wording in Content → Pages → Cancellation policy.')),
                ])->columns(2),

                Forms\Components\Tabs\Tab::make(__('Reviews'))->icon('heroicon-o-star')->schema([
                    Forms\Components\Toggle::make('reviews_enabled')->label(__('Accept guest reviews'))
                        ->helperText(__('Shows the reviews page and the review form on the website.'))->live(),
                    Forms\Components\Toggle::make('reviews_require_booking')->label(__('Only guests with a confirmed reservation can write a review'))
                        ->helperText(__('Guests must enter their booking reference and email. Confirmed, checked-in and completed stays qualify; one review per reservation.'))
                        ->visible(fn (Forms\Get $get) => (bool) $get('reviews_enabled')),
                    Forms\Components\Toggle::make('reviews_auto_approve')->label(__('Publish reviews without approval'))
                        ->helperText(__('Off = every review waits in Reservations → Reviews until staff approve it.'))
                        ->visible(fn (Forms\Get $get) => (bool) $get('reviews_enabled')),
                ])->columns(1),
            ])->persistTabInQueryString(),
        ]);
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label(__('Save settings'))->submit('save')];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach (self::TRANSLATABLE as $k) {
            $data[$k] = [];
            foreach (array_keys(config('heavengate.locales')) as $l) {
                $data[$k][$l] = $data["{$k}_{$l}"] ?? null;
                unset($data["{$k}_{$l}"]);
            }
        }
        $data['weekend_nights'] = array_map('intval', $data['weekend_nights'] ?? []);
        Setting::putMany($data);

        Notification::make()->title(__('Settings saved'))->success()->send();
    }
}
