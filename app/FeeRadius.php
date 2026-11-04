<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
class FeeRadius extends Model
{
    protected $guarded = [];
    public function region() { return $this->belongsTo(Region::class, 'region_code', 'code'); }
}
