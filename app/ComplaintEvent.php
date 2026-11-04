<?php

namespace App;

use App\Concerns\EncryptsComplaintFields;
use Illuminate\Database\Eloquent\Model;

class ComplaintEvent extends Model
{
    use EncryptsComplaintFields;
    protected $guarded = ['id'];
    protected $complaintEncryptedFields = ['public_message','internal_note'];
    protected $hidden = ['internal_note','public_message','actor_id'];
    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }
}
