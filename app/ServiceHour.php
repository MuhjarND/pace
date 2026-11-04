<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceHour extends Model
{
    protected $fillable = ['work_unit_id', 'weekday', 'is_open', 'opens_at', 'closes_at', 'break_start', 'break_end'];
    protected $casts = ['is_open' => 'boolean', 'weekday' => 'integer'];
}
