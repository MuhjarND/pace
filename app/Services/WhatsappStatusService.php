<?php
namespace App\Services;
use App\WhatsappNotification;
use Illuminate\Support\Facades\DB;

class WhatsappStatusService
{
    public function receive(array $data)
    {
        $values=['provider_id'=>$data['id']??null,'state_id'=>$data['stateid']??null,'status'=>strtolower($data['status']??''),'state'=>strtolower($data['state']??'')];
        $fingerprint=hash('sha256',json_encode($values));
        DB::table('whatsapp_status_events')->insertOrIgnore(array_merge($values,['fingerprint'=>$fingerprint,'created_at'=>now(),'updated_at'=>now()]));
        $this->reconcile();
    }
    public function reconcile()
    {
        // Keep early callbacks until the send response has durably saved its provider ID.
        foreach(DB::table('whatsapp_status_events')->whereNull('processed_at')->orderBy('id')->limit(200)->get()as$event){
            DB::transaction(function()use($event){
                $q=WhatsappNotification::query();
                $event->provider_id ? $q->where('provider_id',$event->provider_id) : $q->where('provider_state_id',$event->state_id);
                $n=$q->lockForUpdate()->first();if(!$n)return;
                $next=($event->state==='read'||$event->status==='read')?'read':($event->state==='delivered'||$event->status==='delivered'?'delivered':null);
                $rank=['queued'=>0,'sending'=>0,'accepted'=>1,'uncertain'=>1,'failed'=>1,'delivered'=>2,'read'=>3];
                if($next&&($rank[$next]??0)>($rank[$n->status]??0)){$n->status=$next;if(!$n->delivered_at)$n->delivered_at=now();}
                elseif(in_array($event->status,['failed','invalid','error'],true)&&!in_array($n->status,['delivered','read'],true)){
                    $n->status='failed';$n->last_error='Fonnte melaporkan kegagalan pengiriman. Periksa laporan provider.';
                }
                if($event->state_id)$n->provider_state_id=$event->state_id;
                if($event->status)$n->provider_status=$event->status;
                $n->save();DB::table('whatsapp_status_events')->where('id',$event->id)->update(['processed_at'=>now()]);
            });
        }
    }
}
