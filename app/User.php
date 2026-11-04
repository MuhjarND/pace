<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $attributes = ['is_active' => true, 'auth_version' => 1];

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'avatar', 'is_online', 'work_unit', 'can_monitor',
    ];

    protected $hidden = [
        'password', 'remember_token', 'auth_version',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_online' => 'boolean',
        'can_monitor' => 'boolean',
        'is_active' => 'boolean', 'is_guest' => 'boolean',
        'auth_version' => 'integer',
        'can_handle_complaints' => 'boolean',
    ];

    public function isOperator()
    {
        return $this->role === 'operator';
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function canMonitor()
    {
        return ($this->isAdmin() || $this->isOperator()) && $this->can_monitor;
    }

    public function canManageMasterData()
    {
        return $this->isAdmin() && $this->can_monitor;
    }

    public function hasComplaintAssignment()
    {
        return !$this->is_guest && $this->work_unit === config('ptsp.supervising_work_unit')
            && ($this->isAdmin() || $this->isOperator())
            && ($this->canManageMasterData() || $this->can_handle_complaints);
    }

    public function canHandleComplaints()
    {
        return $this->is_active && $this->hasComplaintAssignment();
    }

    public function canManageComplaintAccess()
    {
        return $this->is_active && !$this->is_guest && $this->canManageMasterData()
            && $this->work_unit === config('ptsp.supervising_work_unit');
    }

    public function chatRooms()
    {
        return $this->hasMany(ChatRoom::class);
    }

    public function adminChatRooms()
    {
        return $this->hasMany(ChatRoom::class , 'admin_id');
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }

    public function callsMade()
    {
        return $this->hasMany(CallLog::class , 'caller_id');
    }

    public function callsReceived()
    {
        return $this->hasMany(CallLog::class , 'receiver_id');
    }
}
