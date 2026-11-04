<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceHoliday extends Model
{
    protected $fillable = ['work_unit_id', 'date', 'name'];
}
