<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['chat_room_id', 'sender_id', 'message', 'type', 'is_read'];

    public function chatRoom()
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class , 'sender_id');
    }
}
