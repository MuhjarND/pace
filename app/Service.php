<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    public function isComplaintChannel()
    {
        return in_array(mb_strtolower(trim((string)$this->category)),['pengaduan','gratifikasi'],true);
    }
    protected $fillable = ['name', 'description', 'icon', 'category', 'is_active'];

    public function chatRooms()
    {
        return $this->hasMany(ChatRoom::class);
    }
}
