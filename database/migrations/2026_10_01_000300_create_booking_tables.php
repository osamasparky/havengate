<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->index();
            $table->string('phone', 40)->nullable();
            $table->char('country', 2)->nullable();
            $table->string('preferred_locale', 5)->default('en');
            $table->text('notes')->nullable();          // internal, staff only
            $table->boolean('marketing_opt_in')->default(false);
            $table->boolean('is_vip')->default(false);
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 16)->unique();  // HG-7K3P9Q
            $table->foreignId('guest_id')->constrained()->restrictOnDelete();
            // pending (held, awaiting payment) | confirmed | checked_in | checked_out | cancelled | expired | no_show
            $table->string('status', 20)->default('pending')->index();
            // unpaid | partial | paid | refunded
            $table->string('payment_status', 20)->default('unpaid');
            $table->string('source', 20)->default('website'); // website | admin | phone | walk_in | ota
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedSmallInteger('nights');
            $table->unsignedTinyInteger('adults');
            $table->unsignedTinyInteger('children')->default(0);
            $table->char('currency', 3)->default('EGP');
            $table->decimal('accommodation_total', 12, 2)->default(0);
            $table->decimal('extras_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('amount_due_now', 12, 2)->default(0); // deposit or full amount
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->foreignId('promotion_id')->nullable()->constrained()->nullOnDelete();
            $table->string('promo_code', 40)->nullable();
            $table->string('locale', 5)->default('en');
            $table->string('arrival_time', 20)->nullable();
            $table->text('special_requests')->nullable();
            $table->text('internal_notes')->nullable();
            $table->boolean('with_pet')->default(false);
            $table->timestamp('expires_at')->nullable();      // hold expiry for pending
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->string('manage_token', 64)->unique();     // signed link for guests
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['check_in', 'check_out']);
        });

        Schema::create('booking_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('accommodation_id')->constrained()->restrictOnDelete();
            // Denormalised for fast overlap checks without joining bookings.
            $table->date('check_in');
            $table->date('check_out');
            $table->boolean('is_active')->default(true)->index(); // false once cancelled/expired
            $table->unsignedTinyInteger('adults');
            $table->unsignedTinyInteger('children')->default(0);
            $table->json('nightly_rates');                      // [{"date":"2026-10-12","rate":3200,"label":"Weekend"}]
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
            $table->index(['unit_id', 'check_in', 'check_out', 'is_active'], 'bu_overlap_idx');
        });

        Schema::create('booking_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('experience_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');                 // snapshot in booking locale
            $table->date('service_date')->nullable();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20);          // easykash | cash | bank_transfer | instapay | manual
            $table->string('method')->nullable();    // card, fawry, wallet... as reported by gateway
            $table->string('reference', 40)->unique(); // our customerReference sent to the gateway
            $table->string('provider_reference')->nullable()->index(); // easykashRef
            $table->string('voucher')->nullable();   // Fawry pay-at-outlet code
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('EGP');
            // initiated | pending | paid | failed | expired | refunded
            $table->string('status', 20)->default('initiated')->index();
            $table->string('type', 20)->default('charge'); // charge | refund
            $table->json('payload')->nullable();     // raw gateway data, for audits
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('booking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);             // created, confirmed, payment_received, cancelled, note...
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('booking_extras');
        Schema::dropIfExists('booking_units');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('guests');
    }
};
