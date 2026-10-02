<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Seasonal rates override or adjust the nightly base price for night
         * dates in [starts_on, ends_on] (inclusive). When several match a night,
         * the highest priority wins; ties go to the most specific (accommodation
         * over global), then the most recent.
         */
        Schema::create('seasonal_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('adjustment_type', 20)->default('fixed'); // fixed | percent | amount
            $table->decimal('value', 12, 2);   // fixed price, ±% or ±amount
            $table->json('weekdays')->nullable(); // [0..6] Carbon dayOfWeek of the NIGHT; null = all
            $table->unsignedTinyInteger('min_nights')->nullable();
            $table->unsignedSmallInteger('priority')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['starts_on', 'ends_on']);
        });

        /*
         * Blocked nights (maintenance, owner use, private events). A row with
         * only accommodation_id blocks every unit of that type; with unit_id a
         * single unit; with neither, the whole camp.
         */
        Schema::create('blocked_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');           // last blocked NIGHT (inclusive)
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['starts_on', 'ends_on']);
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->nullable()->unique(); // null => applied automatically
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('type', 20)->default('percent');   // percent | fixed
            $table->decimal('value', 12, 2);
            $table->date('bookable_from')->nullable();        // booking date window
            $table->date('bookable_until')->nullable();
            $table->date('stay_from')->nullable();            // stay date window
            $table->date('stay_until')->nullable();
            $table->unsignedTinyInteger('min_nights')->nullable();
            $table->json('accommodation_ids')->nullable();    // null => all
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('show_on_site')->default(false);  // banner on home page
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('blocked_dates');
        Schema::dropIfExists('seasonal_rates');
    }
};
