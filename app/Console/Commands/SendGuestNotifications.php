<?php
namespace App\Console\Commands;
use App\Services\FonnteService;
use App\WhatsappNotification;
use Illuminate\Console\Command;

class SendGuestNotifications extends Command
{
    protected $signature = 'ptsp:send-notifications';
    protected $description = 'Deliver queued PTSP ready invitations; never retry uncertain external outcomes';
    public function handle(FonnteService $service)
    {
        WhatsappNotification::where('status','sending')->where('attempted_at','<',now()->subMinutes(2))->update([
            'status'=>'uncertain','last_error'=>'Proses terhenti; periksa laporan Fonnte sebelum mengirim ulang.']);
        foreach (WhatsappNotification::where('status','queued')->orderBy('id')->limit(50)->pluck('id') as $id) $service->deliver($id);
        app(\App\Services\WhatsappStatusService::class)->reconcile();
        $this->info('Queued notifications checked.'); return 0;
    }
}
