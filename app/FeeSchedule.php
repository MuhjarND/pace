<?php
namespace App;
use Illuminate\Database\Eloquent\Model;

class FeeSchedule extends Model
{
    protected $guarded = [];
    protected $casts = ['components'=>'array', 'effective_date'=>'date', 'expires_on'=>'date', 'is_published'=>'boolean'];
    public function unit() { return $this->belongsTo(WorkUnit::class, 'work_unit_id'); }
    public function radii() { return $this->hasMany(FeeRadius::class); }
}
