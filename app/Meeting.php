<?php

namespace App;

use App\Rules\GoogleMeetUrl;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable = ['user_id', 'admin_id', 'topic', 'start_time', 'duration', 'meeting_id', 'join_url', 'status',
        'work_unit', 'slot_id', 'verification_status', 'verified_by', 'verified_at', 'verification_note', 'completed_at', 'queue_date', 'queue_number'];

    protected $casts = [
        'start_time' => 'datetime',
        'duration' => 'integer',
        'verified_at' => 'datetime',
        'completed_at' => 'datetime', 'started_at' => 'datetime',
        'queue_date' => 'date',
        'queue_number' => 'integer', 'called_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function guestTicket() { return $this->hasOne(GuestTicket::class); }

    public function scopeAccessibleTo($query, User $user)
    {
        if ($user->canMonitor()) {
            return $query;
        }

        if ($user->isAdmin() || $user->isOperator()) {
            return $user->work_unit ? $query->where('work_unit', $user->work_unit) : $query->whereRaw('1 = 0');
        }

        return $query->where('user_id', $user->id);
    }

    public function slot()
    {
        return $this->belongsTo(ConsultationSlot::class, 'slot_id');
    }

    public function canManage(User $user)
    {
        return ($user->isAdmin() || $user->isOperator()) && $this->work_unit && $user->work_unit === $this->work_unit;
    }

    public function canJoin(User $user)
    {
        if ((int) $this->user_id !== (int) $user->id && !$this->canManage($user)) {
            return false;
        }
        if (!in_array($this->status, ['scheduled', 'started'], true) || !$this->hasJoinUrl()) {
            return false;
        }
        return in_array($this->verification_status, ['verified', 'immediate'], true)
            && $this->start_time->lte(now());
    }

    public function markStarted()
    {
        if ($this->status !== 'started') {
            $this->forceFill(['status' => 'started', 'started_at' => now()])->save();
        }
    }

    public function queueLabel()
    {
        return 'A'.str_pad((string) ($this->queue_number ?? $this->id), 3, '0', STR_PAD_LEFT);
    }

    public function setQueueDateAttribute($value)
    {
        $this->attributes['queue_date'] = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d') : substr((string) $value, 0, 10);
    }

    public function localStart()
    {
        return $this->start_time->copy()->timezone(config('ptsp.timezone', 'Asia/Jayapura'));
    }

    public function statusLabel()
    {
        if ($this->status === 'scheduled' && $this->called_at) return 'Siap dilayani';
        return ['scheduled' => $this->start_time->gt(now()) ? 'Terjadwal' : 'Mengantre', 'started' => 'Sedang dilayani', 'ended' => 'Selesai', 'cancelled' => 'Dibatalkan'][$this->status] ?? $this->status;
    }

    public function verificationLabel()
    {
        return ['pending' => 'Belum diverifikasi', 'verified' => 'Sudah diverifikasi',
            'rejected' => 'Ditolak petugas', 'immediate' => 'Antrean langsung'][$this->verification_status] ?? 'Jadwal lama';
    }

    public function isGoogleMeet()
    {
        return (new GoogleMeetUrl)->passes('join_url', $this->join_url);
    }

    public function hasJoinUrl()
    {
        if ($this->isGoogleMeet()) {
            return true;
        }

        // Preserve access to existing Zoom appointments after the provider change.
        $url = parse_url($this->join_url ?? '');

        return $url && ($url['scheme'] ?? '') === 'https'
            && !isset($url['user']) && !isset($url['pass']) && !isset($url['port'])
            && preg_match('/\A(?:[a-z0-9-]+\.)?zoom\.us\z/', $url['host'] ?? '') === 1
            && preg_match('~/j/[0-9]+~', $url['path'] ?? '') === 1;
    }
}
