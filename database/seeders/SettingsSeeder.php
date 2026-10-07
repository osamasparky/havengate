<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Booking policies and business details. Every value is editable in
 * Admin → Settings. Prices/taxes are placeholders: confirm with the owner.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::putMany([
            // Contact
            'contact_phone' => '+20 100 000 0000',
            'contact_whatsapp' => '+201000000000',
            'contact_email' => 'reservations@heavengatecamp.com',
            'notification_email' => 'reservations@heavengatecamp.com',
            'address' => [
                'en' => 'Nuweiba–Taba Road, Nuweiba 46621, South Sinai, Egypt',
                'ar' => 'طريق نويبع – طابا، نويبع 46621، جنوب سيناء، مصر',
                'he' => 'כביש נואיבה–טאבה, נואיבה 46621, דרום סיני, מצרים',
            ],
            'instagram' => 'https://www.instagram.com/heavengatecamp/',

            // Stay rules
            'check_in_time' => '14:00',
            'check_out_time' => '11:00',
            'children_max_age' => 11,
            'pets_allowed' => true,
            'pet_fee_per_night' => 0,
            'weekend_nights' => [4, 5],          // Thursday & Friday nights
            'min_lead_days' => 0,                // same-day bookings allowed
            'max_nights' => 30,

            // Money
            'deposit_percent' => 100,            // 100 = pay in full online; e.g. 30 = deposit
            'service_charge_percent' => 0,
            'vat_percent' => 0,
            'online_payment_enabled' => true,
            'offline_payment_enabled' => true,
            'offline_hold_hours' => 24,
            'pay_at_property_enabled' => true,
            'pay_at_property_auto_confirm' => true, // false = staff confirm each one
            'offline_payment_instructions' => [
                'en' => "Transfer the amount due via InstaPay or bank transfer and send the receipt on WhatsApp with your booking reference. Your stay is held for 24 hours.",
                'ar' => "حوّل المبلغ المستحق عبر إنستاباي أو تحويل بنكي وأرسل الإيصال على واتساب مع رقم الحجز. يتم الاحتفاظ بحجزك لمدة 24 ساعة.",
                'he' => "העבירו את הסכום לתשלום בהעברה בנקאית או InstaPay ושלחו את האסמכתא בוואטסאפ עם מספר ההזמנה. ההזמנה נשמרת עבורכם 24 שעות.",
            ],

            // Cancellation policy
            'free_cancellation_days' => 7,
            'late_cancellation_days' => 2,
            'late_cancellation_refund_percent' => 50,

            // Reviews
            'reviews_enabled' => true,
            'reviews_require_booking' => true,   // only guests with a confirmed reservation
            'reviews_auto_approve' => false,     // staff approve each review first
        ]);
    }
}
