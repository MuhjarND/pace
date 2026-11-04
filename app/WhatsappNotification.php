<?php
namespace App;
use Illuminate\Database\Eloquent\Model;

class WhatsappNotification extends Model
{
    protected $guarded = [];
    protected $casts = ['attempted_at' => 'datetime', 'accepted_at' => 'datetime', 'delivered_at' => 'datetime'];
    public function ticket() { return $this->belongsTo(GuestTicket::class, 'guest_ticket_id'); }
    public function statusLabel()
    {
        return ['queued'=>'Menunggu pengiriman', 'sending'=>'Sedang dikirim', 'accepted'=>'Diterima Fonnte; menunggu laporan',
            'delivered'=>'Terkirim ke WhatsApp', 'read'=>'Dibaca', 'failed'=>'Gagal dikirim', 'uncertain'=>'Hasil pengiriman belum pasti',
            'blocked'=>'Konfigurasi pengiriman belum siap', 'cancelled'=>'Pengiriman dibatalkan'][$this->status] ?? 'Menunggu laporan';
    }
}
