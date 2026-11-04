<?php
namespace App\Http\Controllers;
use App\Meeting;
use App\WhatsappNotification;
use App\WorkUnit;
use App\Services\ConsultationQueueService;
use App\Services\FonnteService;
use Illuminate\Support\Facades\DB;

class GuestQueueController extends Controller
{
    public function __construct() { $this->middleware('auth'); }
    public function call($id,FonnteService $fonnte)
    {
        $visible = Meeting::accessibleTo(auth()->user())->findOrFail($id);
        $n = DB::transaction(function () use ($visible) {
            WorkUnit::where('name',$visible->work_unit)->lockForUpdate()->firstOrFail();
            $m = Meeting::whereKey($visible->id)->lockForUpdate()->firstOrFail();
            abort_unless($m->canManage(auth()->user()),403);
            abort_unless($m->status === 'scheduled' && $m->canJoin(auth()->user()) && app(ConsultationQueueService::class)->state($m)['ready'],422,'Verifikasi, waktu, dan giliran antrean harus sesuai sebelum memanggil.');
            $ticket = $m->guestTicket; abort_unless($ticket && $ticket->inviteActive(),422,'Tiket tamu sudah tidak berlaku.');
            if (!$m->called_at) $m->forceFill(['called_at'=>now(),'called_by'=>auth()->id()])->save();
            return WhatsappNotification::firstOrCreate(['guest_ticket_id'=>$ticket->id],['status'=>'queued']);
        });
        $n = $fonnte->deliver($n->id);
        return redirect()->route('operations.queue')->with('success','Tamu dipanggil. WhatsApp: '.$n->statusLabel().'.');
    }
    public function retry($id,FonnteService $fonnte)
    {
        $meeting = Meeting::accessibleTo(auth()->user())->findOrFail($id);
        abort_unless($meeting->canManage(auth()->user()),403);
        abort_unless($meeting->guestTicket && $meeting->guestTicket->notification,404);
        abort_unless($meeting->guestTicket->inviteActive() && $meeting->called_at,422);
        $n = $fonnte->retry($meeting->guestTicket->notification);
        $n = $fonnte->deliver($n->id);
        return redirect()->route('operations.queue')->with('success','WhatsApp: '.$n->statusLabel().'.');
    }
}
