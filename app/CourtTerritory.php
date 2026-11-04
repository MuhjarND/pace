<?php
namespace App;
use Illuminate\Database\Eloquent\Model;

class CourtTerritory extends Model
{
    protected $guarded = [];
    protected $casts = ['is_verified' => 'boolean', 'effective_date' => 'date'];
    public function region() { return $this->belongsTo(Region::class, 'region_code', 'code'); }
    public function unit() { return $this->belongsTo(WorkUnit::class, 'work_unit_id'); }
}
