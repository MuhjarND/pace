<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ConsultationSlot extends Model
{
    protected $fillable = ['work_unit', 'starts_at', 'ends_at', 'is_active', 'created_by'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean'];

    public function bookings()
    {
        return $this->hasMany(Meeting::class, 'slot_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)->where('ends_at', '>', now())
            ->whereDoesntHave('bookings', function ($bookings) {
                $bookings->whereIn('status', ['scheduled', 'started']);
            });
    }

    public function localStart()
    {
        return $this->starts_at->copy()->timezone(config('ptsp.timezone', 'Asia/Jayapura'));
    }

    public function localEnd()
    {
        return $this->ends_at->copy()->timezone(config('ptsp.timezone', 'Asia/Jayapura'));
    }
}
