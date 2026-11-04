<?php
namespace App\Services;

use App\GuestTicket;
use App\Meeting;
use App\WhatsappNotification;
use App\WorkUnit;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FonnteService
{
    public function configurationError()
    {
        if (!config('services.fonnte.enabled') || !config('services.fonnte.token')) return 'Fonnte belum diaktifkan atau token belum dikonfigurasi.';
        $url = parse_url(config('services.fonnte.public_url') ?: '');
        $host = $url['host'] ?? '';
        if (($url['scheme'] ?? '') !== 'https' || !$host || strpos($host,'.') === false || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])
            || preg_match('/(?:\A|\.)(localhost|local|test|invalid)\z/i',$host) || (filter_var($host,FILTER_VALIDATE_IP) && !filter_var($host,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))) {
            return 'Alamat aplikasi HTTPS publik belum dikonfigurasi. Undangan localhost tidak dapat dibuka masyarakat.';
        }
        return null;
    }

    public function deliver($id)
    {
        $visible = WhatsappNotification::with('ticket.meeting')->findOrFail($id);
        // Commit a durable claim BEFORE network I/O. A process crash must not roll back to queued and send twice.
        $claimed = DB::transaction(function () use ($visible) {
            $n = WhatsappNotification::whereKey($visible->id)->lockForUpdate()->firstOrFail();
            if ($n->status !== 'queued') return false;
            $n->update(['status'=>'sending','attempted_at'=>now()]);
            return true;
        });
        if (!$claimed) return $visible->fresh();
        // Serialize call/send/cancel/start across one court. Ambiguous network outcomes are NEVER auto-retried.
        $result = DB::transaction(function () use ($visible) {
            WorkUnit::where('name',$visible->ticket->meeting->work_unit)->lockForUpdate()->firstOrFail();
            $meeting = Meeting::whereKey($visible->ticket->meeting_id)->lockForUpdate()->firstOrFail();
            $ticket = GuestTicket::whereKey($visible->guest_ticket_id)->lockForUpdate()->firstOrFail();
            $ticket->setRelation('meeting',$meeting);
            $n = WhatsappNotification::whereKey($visible->id)->lockForUpdate()->firstOrFail();
            if ($n->status !== 'sending') return $n;
            if (!$ticket->inviteActive() || !$meeting->called_at || !app(ConsultationQueueService::class)->state($meeting)['ready']) {
                $n->update(['status'=>'cancelled','last_error'=>'Konsultasi sudah tidak dapat dipanggil.']); return $n;
            }
            if ($error = $this->configurationError()) { $n->update(['status'=>'blocked','last_error'=>$error]); return $n; }
            $n->update(['status'=>'sending','attempts'=>$n->attempts+1,'attempted_at'=>now(),'last_error'=>null]);
            $url = rtrim(config('services.fonnte.public_url'),'/').'/undangan/'.Crypt::decryptString($ticket->invite_secret);
            $message = 'PTSP '.$meeting->work_unit."\nKonsultasi Google Meet Anda sudah siap dilayani.\nNomor antrean: ".$meeting->queueLabel()
                ."\nJadwal: ".$meeting->localStart()->format('d/m/Y H:i')." WIT\nBuka undangan: ".$url
                ."\nTautan ini khusus untuk konsultasi Anda. Jangan bagikan kepada orang lain.";
            try {
                $response = Http::withHeaders(['Authorization'=>config('services.fonnte.token')])->asForm()->timeout(15)->post('https://api.fonnte.com/send',[
                    'target'=>(string)$ticket->phone,'message'=>$message,'countryCode'=>'62','preview'=>false,'connectOnly'=>true,
                ]);
                $data = $response->json();
                if ($response->successful() && ($data['status'] ?? false) === true && !empty($data['id'][0])) {
                    $n->update(['status'=>'accepted','provider_id'=>(string)$data['id'][0], 'accepted_at'=>now(), 'provider_status'=>(string)($data['process'] ?? 'pending')]);
                } elseif ($response->successful() && (($data['status'] ?? $data['Status'] ?? null) === false)) {
                    // Provider rejection is definitive; never store arbitrary upstream text or credentials.
                    $n->update(['status'=>'failed','last_error'=>'Fonnte menolak pengiriman. Periksa token, koneksi perangkat, dan kuota Fonnte.']);
                } else {
                    $n->update(['status'=>'uncertain','last_error'=>'Respons Fonnte belum memastikan hasil pengiriman. Periksa laporan Fonnte sebelum mengirim ulang.']);
                }
            } catch (\Throwable $e) {
                $n->update(['status'=>'uncertain','last_error'=>'Koneksi terputus atau waktu tunggu habis. Pesan mungkin sudah diproses. Periksa laporan Fonnte.']);
            }
            return $n;
        });
        app(WhatsappStatusService::class)->reconcile();
        return $result->fresh();
    }

    public function retry(WhatsappNotification $visible)
    {
        return DB::transaction(function () use ($visible) {
            $n = WhatsappNotification::whereKey($visible->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($n->status,['failed','blocked'],true) && $n->attempts < 3,422,'Pengiriman ini tidak aman untuk diulang atau sudah mencapai batas percobaan. Periksa laporan Fonnte.');
            // An accepted message keeps its identity; retrying provider-delivery failures may duplicate delivery.
            abort_if($n->provider_id,422,'Pesan sudah diterima Fonnte. Periksa laporan pengiriman.');
            $n->update(['status'=>'queued','last_error'=>null]);
            return $n;
        });
    }
}
