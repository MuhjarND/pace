<?php
namespace Tests\Feature;

use App\ChatMessage;
use App\ChatRoom;
use App\ConsultationSetting;
use App\CourtTerritory;
use App\FeeSchedule;
use App\GuestTicket;
use App\Meeting;
use App\Region;
use App\User;
use App\WhatsappNotification;
use App\WorkUnit;
use App\Services\FonnteService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicAccessTest extends TestCase
{
    use RefreshDatabase;
    public function createApplication() {
        $app=parent::createApplication(); $app['config']->set('database.default','sqlite');
        $app['config']->set('database.connections.sqlite.database',':memory:'); $app['config']->set('hashing.bcrypt.rounds',4);return $app;
    }
    protected function setUp():void {
        parent::setUp(); Carbon::setTestNow(Carbon::parse('2026-10-31 00:00:00','UTC'));
        config(['services.fonnte.token'=>'unit-test-token','services.fonnte.enabled'=>true,'services.fonnte.public_url'=>'https://ptsp.example.go.id',
            'services.fonnte.webhook_secret'=>str_repeat('s',64)]);
        Http::fake(['*'=>new \GuzzleHttp\Promise\FulfilledPromise(new \GuzzleHttp\Psr7\Response(200,['Content-Type'=>'application/json'],json_encode(['status'=>true,'id'=>['msg-1'],'process'=>'pending'])))]);
    }
    protected function tearDown():void { Carbon::setTestNow();parent::tearDown(); }
    private function unit($name='Pengadilan Agama Sorong'){return WorkUnit::where('name',$name)->firstOrFail();}
    private function staff($name='Pengadilan Agama Sorong',$extra=[]){return factory(User::class)->create(array_merge(['role'=>'operator','work_unit'=>$name],$extra));}
    private function regions(){
        foreach([['96',null,1,'Papua Barat Daya'],['96.71','96',2,'Kota Sorong'],['96.71.01','96.71',3,'Sorong'],['96.71.01.1001','96.71.01',4,'Remu Utara'],['96.71.01.1002','96.71.01',4,'Remu']]as$r)
            Region::create(['code'=>$r[0],'parent_code'=>$r[1],'level'=>$r[2],'name'=>$r[3],'source_version'=>'test']);
    }
    private function book($extra=[]){
        $this->get('/konsultasi?new=1')->assertOk();
        return $this->post('/konsultasi',array_merge(['request_key'=>session('public_booking_nonce'),'name'=>'Tamu pengujian','work_unit_id'=>$this->unit()->id,
            'channel'=>'meet','phone'=>'081234567890','topic'=>'Pertanyaan privat uji','consent'=>'1','consultation_date'=>'2026-10-31','consultation_time'=>'now'],$extra));
    }
    private function link(){ConsultationSetting::create(['work_unit'=>$this->unit()->name,'join_url'=>'https://meet.google.com/hrg-wbdh-xvg']);}

    public function test_public_pages_open_without_login_and_never_expose_the_token(){
        foreach(['/','/cari-pengadilan','/perkiraan-biaya','/konsultasi']as$url)$this->get($url)->assertOk()->assertDontSee('unit-test-token');
        $this->assertGuest();
    }
    public function test_pinned_data_is_complete_unique_hierarchical_and_routes_all_regencies(){
        $this->artisan('ptsp:import-regions')->assertExitCode(0);$this->artisan('ptsp:import-court-territories')->assertExitCode(0);
        $this->assertSame(2075,Region::count());$this->assertSame(13,CourtTerritory::count());
        $this->artisan('ptsp:import-regions')->assertExitCode(0);$this->assertSame(2075,Region::count());
        foreach(Region::where('level',2)->get()as$r)$this->assertNotNull(app(\App\Services\CourtFinder::class)->find($r));
        $map=app(\App\Services\CourtFinder::class)->find(Region::where('parent_code','96.04')->firstOrFail());
        $this->assertSame('Pengadilan Agama Sorong',$map->unit->name);
        $this->getJson('/wilayah?parent=96')->assertOk()->assertJsonCount(6,'regions');
    }
    public function test_draft_child_mapping_blocks_parent_and_unknown_never_guesses(){
        $this->regions();$region=Region::findOrFail('96.71.01.1001');$finder=app(\App\Services\CourtFinder::class);
        $this->assertNull($finder->find($region));
        $attributes=['work_unit_id'=>$this->unit()->id,'source_title'=>'SK uji','source_url'=>'https://court.example.go.id/sk','effective_date'=>'2026-01-01'];
        CourtTerritory::create(array_merge($attributes,['region_code'=>'96.71','is_verified'=>true]));
        $this->assertNotNull($finder->find($region));CourtTerritory::create(array_merge($attributes,['region_code'=>'96.71.01','is_verified'=>false]));
        $this->assertNull($finder->find($region));$this->get('/cari-pengadilan?region='.$region->code)->assertOk()->assertSee('Perlu konfirmasi PTSP');
    }
    public function test_guest_chat_creates_separate_principal_and_routes_only_to_own_satker(){
        $staff=$this->staff();$existing=factory(User::class)->create(['phone'=>'6281234567890']);
        $this->book(['channel'=>'chat'])->assertRedirect();$ticket=GuestTicket::firstOrFail();$this->assertGuest();
        $this->assertNotEquals($existing->id,$ticket->user_id);$this->assertNull($ticket->user->phone);$this->assertTrue($ticket->user->is_guest);
        $this->assertEquals($staff->id,$ticket->room->admin_id);
        $this->get('/tiket/'.$ticket->public_id)->assertOk()->assertSee('Pertanyaan privat uji');
        $this->post('/tiket/'.$ticket->public_id.'/pesan',['message'=>'Pesan masyarakat'])->assertRedirect();
        $this->actingAs($staff)->postJson('/chat/send',['chat_room_id'=>$ticket->chat_room_id,'message'=>'Balasan PTSP'])->assertOk();
        $this->getJson('/tiket/'.$ticket->public_id.'/pesan')->assertOk()->assertJsonFragment(['text'=>'Balasan PTSP','mine'=>false]);
        $this->actingAs($this->staff('Pengadilan Agama Manokwari'))->postJson('/chat/send',['chat_room_id'=>$ticket->chat_room_id,'message'=>'Lintas satker'])->assertNotFound();
    }
    public function test_phone_alone_cannot_open_existing_account_or_history(){
        $user=factory(User::class)->create(['phone'=>'6281234567890']);
        $this->post('/welcome/check',['phone'=>$user->phone,'work_unit'=>$user->work_unit])->assertRedirect(route('public.consult'));$this->assertGuest();
        $this->post('/welcome/register',['name'=>'Bukan pemilik','phone'=>$user->phone,'work_unit'=>$user->work_unit])->assertRedirect(route('public.consult'));$this->assertGuest();
        $this->assertSame(1,User::count());
    }
    public function test_session_tickets_are_private_even_when_public_id_is_known(){
        $this->book(['channel'=>'chat'])->assertRedirect();$ticket=GuestTicket::firstOrFail();$this->flushSession();
        $this->get('/tiket/'.$ticket->public_id)->assertNotFound();$this->getJson('/tiket/'.$ticket->public_id.'/pesan')->assertNotFound();
        $this->post('/tiket/'.$ticket->public_id.'/pesan',['message'=>'Tidak boleh'])->assertNotFound();
    }
    public function test_double_submit_reuses_the_same_ticket_and_queue_number(){
        $this->link();$this->book()->assertRedirect();$ticket=GuestTicket::firstOrFail();
        $this->post('/konsultasi',['request_key'=>session('public_booking_nonce'),'name'=>'Tamu pengujian','work_unit_id'=>$this->unit()->id,
            'channel'=>'meet','phone'=>'081234567890','topic'=>'Pertanyaan privat uji','consent'=>'1','consultation_date'=>'2026-10-31','consultation_time'=>'now'])->assertRedirect(route('public.ticket',$ticket->public_id));
        $this->assertSame(1,GuestTicket::count());$this->assertSame(1,Meeting::count());$this->assertSame('6281234567890',$ticket->phone);Http::assertNothingSent();
    }
    public function test_schedules_require_verification_time_turn_and_explicit_call(){
        $this->link();$staff=$this->staff();$this->book(['consultation_date'=>'2026-11-01','consultation_time'=>'10:00'])->assertRedirect();$ticket=GuestTicket::firstOrFail();$m=$ticket->meeting;
        $this->assertSame('pending',$m->verification_status);$this->actingAs($staff)->post('/meetings/'.$m->id.'/call-guest')->assertStatus(422);
        $this->post('/meetings/'.$m->id.'/verify',['decision'=>'verified'])->assertRedirect();$this->post('/meetings/'.$m->id.'/call-guest')->assertStatus(422);
        Carbon::setTestNow('2026-11-01 01:00:00');$this->post('/meetings/'.$m->id.'/start')->assertStatus(422);
        $this->post('/meetings/'.$m->id.'/call-guest')->assertRedirect();$this->assertNotNull($m->fresh()->called_at);$this->assertSame('scheduled',$m->fresh()->status);
        $this->getJson('/tiket/'.$ticket->public_id.'/status')->assertJsonPath('can_join',true);
    }
    public function test_ready_message_is_idempotent_private_and_not_claimed_delivered(){
        $this->link();$staff=$this->staff();$this->book()->assertRedirect();$t=GuestTicket::firstOrFail();
        $this->actingAs($staff)->post('/meetings/'.$t->meeting_id.'/call-guest')->assertRedirect();
        $this->post('/meetings/'.$t->meeting_id.'/call-guest')->assertRedirect();Http::assertSentCount(1);
        Http::assertSent(function($r){return $r->url()==='https://api.fonnte.com/send' && $r['preview']===false && $r['target']==='6281234567890'
            && strpos($r['message'],'Pertanyaan privat uji')===false && strpos($r['message'],'https://ptsp.example.go.id/undangan/')!==false;});
        $this->assertSame('accepted',$t->notification->status);$this->assertNull($t->notification->delivered_at);
        $this->get('/operations/queue')->assertOk()->assertSee('Diterima Fonnte');
    }
    public function test_other_satker_and_monitor_cannot_call_or_retry_guests(){
        $this->link();$this->book()->assertRedirect();$t=GuestTicket::firstOrFail();
        $this->actingAs($this->staff('Pengadilan Agama Manokwari'))->post('/meetings/'.$t->meeting_id.'/call-guest')->assertNotFound();
        $this->actingAs($this->staff('Pengadilan Tinggi Agama Papua Barat',['can_monitor'=>true]))->post('/meetings/'.$t->meeting_id.'/call-guest')->assertForbidden();Http::assertNothingSent();
    }
    public function test_invitation_get_is_read_only_and_never_exposes_chat_or_topic(){
        $this->link();$this->book()->assertRedirect();$t=GuestTicket::firstOrFail();$token=Crypt::decryptString($t->invite_secret);
        $this->get('/undangan/'.$token)->assertOk()->assertDontSee('Pertanyaan privat uji')->assertDontSee('Tamu pengujian')->assertHeader('Cache-Control','no-store, private');
        $this->assertSame('scheduled',$t->meeting->fresh()->status);$this->assertNull($t->meeting->fresh()->started_at);
        $this->post('/undangan/'.$token)->assertStatus(422);$this->get('/undangan/'.str_repeat('z',64))->assertNotFound();
    }
    public function test_join_does_not_start_service_and_completion_revokes_invitation(){
        $this->link();$this->book()->assertRedirect();$t=GuestTicket::firstOrFail();$token=Crypt::decryptString($t->invite_secret);
        $this->actingAs($this->staff())->post('/meetings/'.$t->meeting_id.'/call-guest')->assertRedirect();
        $this->post('/tiket/'.$t->public_id.'/gabung')->assertRedirect('https://meet.google.com/hrg-wbdh-xvg');
        $this->post('/undangan/'.$token)->assertRedirect('https://meet.google.com/hrg-wbdh-xvg');$this->assertNull($t->meeting->fresh()->started_at);
        $this->post('/meetings/'.$t->meeting_id.'/complete')->assertStatus(422);
        $this->post('/meetings/'.$t->meeting_id.'/start')->assertRedirect();$this->assertNotNull($t->meeting->fresh()->started_at);
        $this->post('/meetings/'.$t->meeting_id.'/complete')->assertRedirect();
        $this->get('/undangan/'.$token)->assertStatus(410);$this->post('/undangan/'.$token)->assertStatus(410);
        $this->getJson('/tiket/'.$t->public_id.'/status')->assertJsonPath('can_join',false);
    }
    public function test_queue_prevents_next_call_and_called_head_cannot_be_displaced(){
        $this->link();$staff=$this->staff();$this->book()->assertRedirect();$first=GuestTicket::firstOrFail();
        $this->book()->assertRedirect();$second=GuestTicket::orderByDesc('id')->firstOrFail();
        $this->actingAs($staff)->post('/meetings/'.$second->meeting_id.'/call-guest')->assertStatus(422);
        $this->post('/meetings/'.$first->meeting_id.'/call-guest')->assertRedirect();
        $second->meeting->update(['start_time'=>now()->subHours(1)]);
        $state=app(\App\Services\ConsultationQueueService::class)->state($first->meeting->fresh());$this->assertTrue($state['ready']);
        $this->assertSame(1,app(\App\Services\ConsultationQueueService::class)->state($second->meeting->fresh())['ahead']);
    }
    public function test_localhost_configuration_blocks_external_send_but_keeps_ticket_ready(){
        config(['services.fonnte.public_url'=>'http://localhost/ptsp_online/public']);$this->link();$this->book()->assertRedirect();$t=GuestTicket::firstOrFail();
        $this->actingAs($this->staff())->post('/meetings/'.$t->meeting_id.'/call-guest')->assertRedirect();
        $this->assertSame('blocked',$t->notification->status);Http::assertNothingSent();
        $this->getJson('/tiket/'.$t->public_id.'/status')->assertJsonPath('can_join',true);
    }
    public function test_uncertain_send_is_never_retried_automatically_or_by_double_click(){
        Http::swap(new \Illuminate\Http\Client\Factory); Http::fake(function(){throw new \RuntimeException('timeout');});$this->link();$this->book()->assertRedirect();$t=GuestTicket::firstOrFail();
        $this->actingAs($this->staff())->post('/meetings/'.$t->meeting_id.'/call-guest')->assertRedirect();$n=$t->notification;$this->assertSame('uncertain',$n->status);
        $this->post('/meetings/'.$t->meeting_id.'/retry-wa')->assertStatus(422);$this->artisan('ptsp:send-notifications')->assertExitCode(0);$this->assertSame(1,(int)$n->fresh()->attempts);
    }
    public function test_webhook_is_authenticated_idempotent_and_does_not_regress_read_state(){
        $this->link();$this->book()->assertRedirect();$t=GuestTicket::firstOrFail();$this->actingAs($this->staff())->post('/meetings/'.$t->meeting_id.'/call-guest');
        $this->postJson('/api/fonnte/status/wrong',['id'=>'msg-1','state'=>'read'])->assertNotFound();
        $url='/api/fonnte/status/'.str_repeat('s',64);
        $this->postJson($url,['id'=>'msg-1','stateid'=>'state-1','state'=>'read'])->assertOk();
        $this->postJson($url,['id'=>'msg-1','status'=>'pending'])->assertOk();
        $this->postJson($url,['id'=>'msg-1','status'=>'failed'])->assertOk();
        $this->assertSame('read',$t->notification->fresh()->status);$this->assertNotNull($t->notification->fresh()->delivered_at);
    }
    public function test_cancel_and_expiry_block_invites_and_chat_data(){
        $this->link();$this->book()->assertRedirect();$t=GuestTicket::firstOrFail();$token=Crypt::decryptString($t->invite_secret);
        $this->delete('/tiket/'.$t->public_id)->assertRedirect();$this->get('/undangan/'.$token)->assertStatus(410);
        $t->update(['expires_at'=>now()->subSecond()]);$this->get('/tiket/'.$t->public_id)->assertStatus(410);
    }
    public function test_guest_availability_obeys_unit_quota_and_missing_link(){
        $this->getJson('/konsultasi/jadwal/'.$this->unit()->id.'?date=2026-10-31')->assertJsonPath('can_now',false);
        $this->link();$unit=$this->unit();$unit->update(['schedule_enabled'=>true,'daily_quota'=>1]);
        $unit->hours()->create(['weekday'=>6,'is_open'=>true,'opens_at'=>'08:00','closes_at'=>'16:00']);
        $this->book()->assertRedirect();$this->book()->assertSessionHasErrors('consultation_time');$this->assertSame(1,GuestTicket::count());
    }
    public function test_unknown_or_unpublished_or_expired_tariff_never_produces_zero_estimate(){
        $this->regions();$unit=$this->unit();$service=app(\App\Services\FeeEstimator::class);
        $parties=[['role'=>'applicant','region_code'=>'96.71.01.1001']];
        $result=$service->estimate($unit,'perwalian',$parties);$this->assertFalse($result['available']);$this->assertNull($result['total']);
        $s=FeeSchedule::create(['work_unit_id'=>$unit->id,'case_type'=>'perwalian','source_title'=>'SK uji','source_url'=>'https://court.example.go.id/sk','effective_date'=>'2026-01-01','is_published'=>false,
            'components'=>[['label'=>'Panggilan','basis'=>'radius_applicant','amount'=>0,'quantity'=>2]]]);
        $this->assertNull($service->estimate($unit,'perwalian',$parties)['total']);$s->update(['is_published'=>true]);$this->assertNull($service->estimate($unit,'perwalian',$parties)['total']);
        $s->radii()->create(['region_code'=>'96.71','label'=>'Radius uji','amount'=>100000]);$this->assertSame(200000,$service->estimate($unit,'perwalian',$parties)['total']);
        $s->update(['expires_on'=>'2026-01-02']);$this->assertNull($service->estimate($unit,'perwalian',$parties)['total']);
    }
    public function test_estimate_calculates_party_specific_radius_and_preserves_consultation_context(){
        $this->regions();$s=FeeSchedule::create(['work_unit_id'=>$this->unit()->id,'case_type'=>'cerai_gugat','source_title'=>'SK uji','source_url'=>'https://court.example.go.id/sk','effective_date'=>'2026-01-01','is_published'=>true,
            'components'=>[['label'=>'Daftar','basis'=>'fixed','amount'=>30000,'quantity'=>1],['label'=>'Panggil P','basis'=>'radius_applicant','amount'=>0,'quantity'=>2],['label'=>'Panggil T','basis'=>'radius_respondent','amount'=>0,'quantity'=>3]]]);
        $s->radii()->create(['region_code'=>'96.71','label'=>'Induk','amount'=>100000]);$s->radii()->create(['region_code'=>'96.71.01.1002','label'=>'Khusus','amount'=>200000]);
        $parties=[['role'=>'applicant','region_code'=>'96.71.01.1001'],['role'=>'respondent','region_code'=>'96.71.01.1002']];
        $this->post('/perkiraan-biaya',['work_unit_id'=>$this->unit()->id,'case_type'=>'cerai_gugat','parties'=>$parties])->assertRedirect()->assertSessionHas('fee_estimate.total',830000);
        $this->get('/perkiraan-biaya?unit='.$this->unit()->id)->assertOk()->assertSee('830.000');
        $this->book(['channel'=>'chat'])->assertRedirect();$this->assertSame(830000,GuestTicket::firstOrFail()->context['total']);
    }
    public function test_only_central_admin_can_map_and_only_own_staff_can_publish_tariffs(){
        $this->regions();$staff=$this->staff();$this->actingAs($staff)->get('/operations/public-data')->assertOk();
        $this->post('/operations/public-data/territories',[])->assertForbidden();
        $data=['work_unit_id'=>$this->unit()->id,'case_type'=>'perwalian','source_title'=>'SK uji','source_url'=>'https://court.example.go.id/sk','effective_date'=>'2026-01-01','is_published'=>'1',
            'components'=>[['label'=>'Daftar','basis'=>'fixed','amount'=>30000,'quantity'=>1]]];
        $this->post('/operations/public-data/tariffs',$data)->assertRedirect();$this->assertTrue(FeeSchedule::firstOrFail()->is_published);
        $this->actingAs($this->staff('Pengadilan Agama Manokwari'))->post('/operations/public-data/tariffs',$data)->assertForbidden();
        $this->actingAs($this->staff('Pengadilan Tinggi Agama Papua Barat',['role'=>'admin','can_monitor'=>true]))->get('/operations/public-data')->assertOk();
    }
    public function test_guest_principal_cannot_login_with_password(){
        $u=factory(User::class)->create(['password'=>Hash::make('Known-For-Test')]);$u->forceFill(['is_guest'=>true])->save();
        $this->post('/login',['email'=>$u->email,'password'=>'Known-For-Test'])->assertSessionHasErrors('email');$this->assertGuest();
    }
    public function test_early_provider_callback_is_reconciled_after_send_response(){
        app(\App\Services\WhatsappStatusService::class)->receive(['id'=>'msg-1','state'=>'read','stateid'=>'early-state']);
        $this->link();$this->book()->assertRedirect();$t=GuestTicket::firstOrFail();
        $this->actingAs($this->staff())->post('/meetings/'.$t->meeting_id.'/call-guest')->assertRedirect();
        $this->assertSame('read',$t->notification->fresh()->status);
        $this->assertNotNull($t->notification->fresh()->delivered_at);Http::assertSentCount(1);
    }
}
