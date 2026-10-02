<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Guest extends Model
{
    use Notifiable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['marketing_opt_in' => 'boolean', 'is_vip' => 'boolean'];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->latest('check_in');
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->first_name} {$this->last_name}"));
    }
}
