<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog: what the camp sells — accommodation types, physical units,
 * facilities, experiences and photos. Translatable columns are JSON
 * (spatie/laravel-translatable) keyed by locale: {"en": "...", "ar": "...", "he": "..."}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accommodations', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('tagline')->nullable();
            $table->json('description')->nullable();
            $table->json('highlights')->nullable();        // one per line, per locale
            $table->json('bed_configuration')->nullable(); // "1 king bed or 2 singles"
            $table->string('category', 30)->default('chalet'); // chalet | hut | family | suite
            $table->unsignedTinyInteger('base_occupancy')->default(2); // guests included in base price
            $table->unsignedTinyInteger('max_adults')->default(2);
            $table->unsignedTinyInteger('max_children')->default(0);
            $table->unsignedTinyInteger('max_guests')->default(2);
            $table->unsignedSmallInteger('size_sqm')->nullable();
            $table->json('features')->nullable();          // ["sea_view","ac","private_bathroom",...]
            $table->decimal('base_price', 12, 2);          // per night, base occupancy
            $table->decimal('weekend_price', 12, 2)->nullable(); // Thu/Fri nights if set
            $table->decimal('extra_adult_fee', 12, 2)->default(0);  // per night above base occupancy
            $table->decimal('extra_child_fee', 12, 2)->default(0);
            $table->unsignedTinyInteger('min_nights')->default(1);
            $table->string('cover_image')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->unique();          // "C-01"
            $table->string('name')->nullable();            // optional nickname, e.g. "Sunrise"
            $table->string('zone')->nullable();            // "Front row", "Garden"
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('icon', 40)->default('star');   // key in resources/views/components/icon.blade.php
            $table->string('category', 30)->default('camp'); // camp | dining | beach | service
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('accommodation_facility', function (Blueprint $table) {
            $table->foreignId('accommodation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->primary(['accommodation_id', 'facility_id']);
        });

        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('summary')->nullable();
            $table->json('description')->nullable();
            $table->json('schedule')->nullable();          // "Daily at sunrise", "Every full moon"
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('pricing_unit', 20)->default('per_person'); // per_person | per_group
            $table->unsignedSmallInteger('max_people')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_addon')->default(true);    // can be added during booking
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('photoable');           // null => general gallery
            $table->string('path');
            $table->json('caption')->nullable();
            $table->json('alt')->nullable();
            $table->string('category', 30)->default('camp'); // camp | stay | beach | night | food | experience
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
        Schema::dropIfExists('experiences');
        Schema::dropIfExists('accommodation_facility');
        Schema::dropIfExists('facilities');
        Schema::dropIfExists('units');
        Schema::dropIfExists('accommodations');
    }
};
