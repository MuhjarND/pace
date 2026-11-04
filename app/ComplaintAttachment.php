<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ComplaintAttachment extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['path','sha256'];
    public function complaint() { return $this->belongsTo(Complaint::class); }
}
