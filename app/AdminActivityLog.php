<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    protected $fillable = ['actor_id', 'action', 'target_type', 'target_id', 'changes'];
    protected $casts = ['changes' => 'array'];
    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }
}
