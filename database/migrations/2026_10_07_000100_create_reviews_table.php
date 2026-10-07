<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guest ratings & reviews. Shown on the site once approved by staff
        // (or straight away when "auto-approve" is on in Settings → Reviews).
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            // One review per reservation for the stay/camp, plus one per experience (enforced in Review::alreadyReviewed()).
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('accommodation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('experience_id')->nullable()->constrained()->nullOnDelete(); // set = review of an experience
            $table->string('name');
            $table->string('email');
            $table->string('country', 80)->nullable();
            $table->unsignedTinyInteger('rating');      // 1–5
            $table->string('title')->nullable();
            $table->text('comment');
            $table->string('locale', 5)->default('en');
            $table->boolean('is_verified')->default(false); // tied to a confirmed reservation
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false); // pinned on the home page
            $table->text('reply')->nullable();               // public answer from the camp
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            $table->index(['is_approved', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
