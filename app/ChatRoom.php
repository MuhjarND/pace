<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChatRoom extends Model
{
    protected $fillable = ['user_id', 'admin_id', 'service_id', 'work_unit', 'status'];

    public function scopeAccessibleTo($query, User $user)
    {
        if ($user->canMonitor()) {
            return $query;
        }

        if ($user->isAdmin() || $user->isOperator()) {
            return $user->work_unit
                ? $query->where('work_unit', $user->work_unit)
                : $query->whereRaw('1 = 0');
        }

        return $query->where('user_id', $user->id);
    }

    public function canReply(User $user)
    {
        return $this->status === 'open' && $this->work_unit
            && $this->work_unit === $user->work_unit
            && ((int) $this->user_id === (int) $user->id || $user->isAdmin() || $user->isOperator());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class , 'admin_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class)->latest();
    }

    public function unreadCount()
    {
        $query = $this->messages()->where('is_read', false);

        if (auth()->user()->isAdmin() || auth()->user()->isOperator()) {
            $query->where('sender_id', $this->user_id);
        } else {
            $query->where('sender_id', '!=', auth()->id());
        }

        return $query->count();
    }
}
