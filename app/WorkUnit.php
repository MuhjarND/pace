<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WorkUnit extends Model
{
    protected $fillable = ['name', 'code', 'is_active', 'address', 'phone', 'schedule_enabled', 'daily_quota', 'website', 'public_requirements'];
    protected $casts = ['is_active' => 'boolean', 'schedule_enabled' => 'boolean', 'daily_quota' => 'integer'];

    public static function activeNames()
    {
        return static::where('is_active', true)->orderBy('id')->pluck('name')->all();
    }

    public static function knownNames()
    {
        return static::orderBy('id')->pluck('name')->all();
    }

    public function hours() { return $this->hasMany(ServiceHour::class); }
    public function holidays() { return $this->hasMany(ServiceHoliday::class); }
}
