<?php
namespace App;
use Illuminate\Database\Eloquent\Model;

class GuestTicket extends Model
{
    protected $guarded = [];
    protected $hidden = ['phone', 'invite_hash', 'invite_secret', 'request_key', 'context'];
    protected $casts = ['context' => 'array', 'expires_at' => 'datetime', 'consented_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
    public function meeting() { return $this->belongsTo(Meeting::class); }
    public function room() { return $this->belongsTo(ChatRoom::class, 'chat_room_id'); }
    public function notification() { return $this->hasOne(WhatsappNotification::class); }
    public function inviteActive()
    {
        return $this->expires_at->gt(now()) && $this->meeting && in_array($this->meeting->status, ['scheduled', 'started'], true)
            && in_array($this->meeting->verification_status, ['immediate', 'verified'], true);
    }
}
