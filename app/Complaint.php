<?php

namespace App;

use App\Concerns\EncryptsComplaintFields;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use EncryptsComplaintFields;

    const TYPES = ['service'=>'Pengaduan layanan', 'gratification'=>'Laporan gratifikasi'];
    const STATUSES = ['received'=>'Diterima', 'reviewing'=>'Ditelaah', 'follow_up'=>'Ditindaklanjuti', 'completed'=>'Selesai'];
    protected $guarded = ['id'];
    protected $complaintEncryptedFields = ['subject','description','location','reported_person','reporter_name','reporter_contact'];
    protected $hidden = ['request_hash','access_hash','subject','description','location','reported_person','reporter_name','reporter_contact'];
    protected $casts = ['occurred_on'=>'date','consented_at'=>'datetime','is_anonymous'=>'boolean','lock_version'=>'integer',
        'assigned_to'=>'integer','work_unit_id'=>'integer'];

    public function unit() { return $this->belongsTo(WorkUnit::class, 'work_unit_id'); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function attachments() { return $this->hasMany(ComplaintAttachment::class); }
    public function events() { return $this->hasMany(ComplaintEvent::class); }
    public function statusLabel() { return static::STATUSES[$this->status] ?? 'Diterima'; }
    public function typeLabel() { return static::TYPES[$this->type] ?? 'Pengaduan layanan'; }
}
