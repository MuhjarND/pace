<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ConsultationJourney extends Model
{
    protected $guarded = [];
    protected $hidden = ['origin', 'origin_region_code', 'destination_latitude', 'destination_longitude'];
    protected $casts = ['calculated_at'=>'datetime', 'distance_meters'=>'integer', 'duration_seconds'=>'integer',
        'fuel_cost'=>'integer', 'fuel_price'=>'integer', 'km_per_liter'=>'float'];

    public function setOriginAttribute($value)
    {
        $this->attributes['origin'] = $value === null ? null : Crypt::encryptString(json_encode($value));
    }

    public function getOriginAttribute($value)
    {
        return $value === null ? null : json_decode(Crypt::decryptString($value), true);
    }

    public function ticket() { return $this->belongsTo(GuestTicket::class, 'guest_ticket_id'); }
    public function unit() { return $this->belongsTo(WorkUnit::class, 'work_unit_id'); }
    public function originRegion() { return $this->belongsTo(Region::class, 'origin_region_code', 'code'); }
}
