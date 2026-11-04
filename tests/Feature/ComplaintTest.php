<?php

namespace Tests\Feature;

use App\Complaint;
use App\ComplaintAttachment;
use App\ComplaintEvent;
use App\User;
use App\WorkUnit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintTest extends TestCase
{
    use RefreshDatabase;
    public function createApplication()
    {
        $app=parent::createApplication();$app['config']->set('database.default','sqlite');
        $app['config']->set('database.connections.sqlite.database',':memory:');$app['config']->set('hashing.bcrypt.rounds',4);return $app;
    }
    protected function setUp(): void
    {
        parent::setUp();Carbon::setTestNow(Carbon::parse('2026-11-02 01:00:00','UTC'));Storage::fake('local');Http::fake();
    }
    protected function tearDown(): void { Carbon::setTestNow();parent::tearDown(); }
    private function unit() { return WorkUnit::where('name','Pengadilan Agama Sorong')->firstOrFail(); }
    private function actor($extra=[])
    {
        $flag=$extra['can_handle_complaints']??false;unset($extra['can_handle_complaints']);
        $u=factory(User::class)->create(array_merge(['role'=>'operator','can_monitor'=>false,'work_unit'=>'Pengadilan Agama Sorong'],$extra));
        $u->forceFill(['can_handle_complaints'=>$flag])->save();return $u;
    }
    private function root() { return $this->actor(['role'=>'admin','can_monitor'=>true,'work_unit'=>config('ptsp.supervising_work_unit')]); }
    private function asActor($u) { return $this->actingAs($u)->withSession(['auth_version'=>$u->auth_version]); }
    private function payload($extra=[])
    {
        $this->get('/pengaduan')->assertOk();$keys=session('complaint_form_keys');$nonce=array_key_last($keys);
        return array_merge(['request_key'=>$nonce,'work_unit_id'=>$this->unit()->id,'type'=>'service','subject'=>'Judul laporan pengujian',
            'description'=>'Kronologi privat pengujian yang tidak boleh terlihat oleh petugas satker lain.',
            'occurred_on'=>'2026-11-01','location'=>'Lokasi privat','reported_person'=>'Pihak terkait privat','consent'=>'1'],$extra);
    }
    private function submit($extra=[])
    {
        $response=$this->from('/pengaduan')->post('/pengaduan',$this->payload($extra));$response->assertRedirect();return Complaint::latest('id')->firstOrFail();
    }
    private function accountPayload($user,$extra=[])
    {
        return array_merge(['name'=>$user->name,'email'=>$user->email,'phone'=>null,'role'=>$user->role,
            'work_unit'=>$user->work_unit,'can_monitor'=>(int)$user->can_monitor,'is_active'=>1,'password'=>null,'password_confirmation'=>null],$extra);
    }
    public function test_public_pages_are_private_no_login_and_send_no_external_messages()
    {
        foreach(['/pengaduan','/pengaduan/lacak']as$url)$this->get($url)->assertOk()->assertHeader('Cache-Control','no-store, private')->assertHeader('X-Robots-Tag','noindex, nofollow');
        $this->get('/')->assertOk()->assertSee(route('public.complaints.create'));$this->assertGuest();Http::assertNothingSent();
    }
    public function test_anonymous_complaint_is_created_once_with_encrypted_contents_and_private_receipt()
    {
        $data=$this->payload();$this->post('/pengaduan',$data)->assertRedirect();$report=Complaint::firstOrFail();$secret=session('complaint_receipt.secret');
        $this->assertTrue($report->is_anonymous);$this->assertSame(32,strlen($secret));$this->assertSame(hash('sha256',$secret),$report->access_hash);
        $raw=DB::table('complaints')->first();$this->assertNotSame($data['subject'],$raw->subject);$this->assertNotSame($data['description'],$raw->description);
        $this->assertSame($data['description'],Crypt::decryptString($raw->description));$this->assertSame(0,User::count());
        $this->get('/pengaduan/tiket/'.$report->reference)->assertOk()->assertSee('Simpan kode rahasia Anda')->assertSee($secret)->assertSee($data['subject']);
        $this->post('/pengaduan',$data)->assertRedirect(route('public.complaints.show',$report->reference));
        $this->assertSame(1,Complaint::count());$this->assertSame(1,ComplaintEvent::count());Http::assertNothingSent();
    }
    public function test_gratification_discards_identity_fields_even_when_supplied_in_the_payload()
    {
        $report=$this->submit(['type'=>'gratification','reporter_name'=>'Nama pelapor privat','reporter_contact'=>'private-contact@example.test']);
        $this->get('/pengaduan/tiket/'.$report->reference)->assertOk()->assertSee('Laporan gratifikasi')->assertDontSee('Nama pelapor privat')->assertDontSee('private-contact@example.test');
        $this->asActor($this->root())->get('/pta/pengaduan/'.$report->reference)->assertOk()->assertSee('Laporan anonim')->assertDontSee('Nama pelapor privat')->assertDontSee('private-contact@example.test');
        $this->assertTrue($report->is_anonymous);
        $this->assertNull(DB::table('complaints')->value('reporter_name'));$this->assertNull(DB::table('complaints')->value('reporter_contact'));
    }
    public function test_only_active_pta_admin_and_explicitly_appointed_pta_handlers_can_read_or_update()
    {
        $report=$this->submit();
        foreach([['role'=>'user'],['role'=>'operator'],['role'=>'admin','can_monitor'=>true],
            ['work_unit'=>config('ptsp.supervising_work_unit'),'can_monitor'=>true],
            ['can_handle_complaints'=>true]]as$extra){
            $actor=$this->actor($extra);$this->asActor($actor)->get('/pta/pengaduan')->assertForbidden();
            $this->get('/pta/pengaduan/'.$report->reference)->assertForbidden();
            $this->post('/pta/pengaduan/'.$report->reference,['status'=>'completed','lock_version'=>1,'public_message'=>'Tidak berhak'])->assertForbidden();
        }
        $handler=$this->actor(['work_unit'=>config('ptsp.supervising_work_unit'),'can_handle_complaints'=>true]);
        $this->asActor($handler)->get('/pta/pengaduan')->assertOk()->assertSee($report->reference);
        $this->asActor($this->root())->get('/pta/pengaduan/'.$report->reference)->assertOk();
    }
    public function test_reference_alone_and_other_session_cannot_open_report_or_evidence()
    {
        $report=$this->submit(['evidence'=>[UploadedFile::fake()->create('private-original-name.pdf',3,'application/pdf')]]);
        $file=$report->attachments()->firstOrFail();$this->flushSession();
        $this->get('/pengaduan/tiket/'.$report->reference)->assertNotFound();
        $this->get('/pengaduan/tiket/'.$report->reference.'/bukti/'.$file->id)->assertNotFound();
        $this->asActor($this->actor())->get('/pta/pengaduan/'.$report->reference.'/bukti/'.$file->id)->assertForbidden();
    }
    public function test_tracking_needs_both_values_and_does_not_flash_access_code()
    {
        $report=$this->submit();$secret=session('complaint_receipt.secret');$this->flushSession();
        $this->from('/pengaduan/lacak')->post('/pengaduan/lacak',['reference'=>$report->reference,'access_code'=>'wrong-code'])
            ->assertSessionHasErrors('access_code');$this->assertNull(session('_old_input.access_code'));
        $this->post('/pengaduan/lacak',['reference'=>$report->reference,'access_code'=>$secret])->assertRedirect(route('public.complaints.show',$report->reference));
        $this->get('/pengaduan/tiket/'.$report->reference)->assertOk();
        Carbon::setTestNow(now()->addHours(3));$this->get('/pengaduan/tiket/'.$report->reference)->assertNotFound();
        $this->post('/pengaduan/lacak',['reference'=>$report->reference,'access_code'=>$secret])->assertRedirect();
    }
    public function test_private_files_are_encrypted_force_downloaded_and_scoped_to_one_report()
    {
        $report=$this->submit(['evidence'=>[UploadedFile::fake()->create('private-name.pdf',3,'application/pdf')]]);$file=$report->attachments()->firstOrFail();
        $encrypted=Storage::disk('local')->get($file->path);$plain=base64_decode(Crypt::decryptString($encrypted));
        $this->assertNotSame($plain,$encrypted);$this->assertSame(hash('sha256',$plain),$file->sha256);
        $this->assertSame('Bukti 1.pdf',$file->name);
        $this->get('/pengaduan/tiket/'.$report->reference.'/bukti/'.$file->id)->assertOk()->assertHeader('Content-Type','application/pdf')
            ->assertHeader('X-Content-Type-Options','nosniff')->assertHeader('Content-Disposition','attachment; filename="Bukti 1.pdf"');
        $other=$this->submit();$this->get('/pengaduan/tiket/'.$other->reference.'/bukti/'.$file->id)->assertNotFound();
        $this->asActor($this->root())->get('/pta/pengaduan/'.$other->reference.'/bukti/'.$file->id)->assertNotFound();
        $this->get('/pta/pengaduan/'.$report->reference.'/bukti/'.$file->id)->assertOk();
    }
    public function test_invalid_file_types_sizes_counts_and_future_dates_are_rejected()
    {
        $sets=[['evidence'=>[UploadedFile::fake()->create('evil.html',1,'text/html')]],
            ['evidence'=>[UploadedFile::fake()->create('large.pdf',5121,'application/pdf')]],
            ['evidence'=>array_map(function(){return UploadedFile::fake()->create('proof.pdf',1,'application/pdf');},range(1,4))],
            ['occurred_on'=>'2026-11-03']];
        foreach($sets as$extra){$this->from('/pengaduan')->post('/pengaduan',$this->payload($extra))->assertSessionHasErrors();}
        $this->assertSame(0,Complaint::count());$this->assertSame([],Storage::disk('local')->allFiles());
    }
    public function test_nonce_and_consent_are_required_without_requiring_reporter_identity()
    {
        $data=$this->payload(['request_key'=>str_repeat('x',48)]);$this->post('/pengaduan',$data)->assertStatus(419);
        $this->post('/pengaduan',$this->payload(['consent'=>'0']))->assertSessionHasErrors('consent');
        $this->assertSame(0,Complaint::count());
        $this->post('/pengaduan',$this->payload())->assertSessionHasNoErrors()->assertRedirect();$this->assertSame(1,Complaint::count());
    }

    public function test_anonymous_form_and_authenticated_submission_never_copy_account_identity()
    {
        $actor=$this->actor(['role'=>'user','name'=>'Account owner private','email'=>'owner-private@example.test','phone'=>'628123450000']);
        $this->asActor($actor)->get('/pengaduan')->assertOk()->assertDontSee('name="reporter_name"',false)->assertDontSee('name="reporter_contact"',false);
        $report=$this->submit();$this->assertTrue($report->is_anonymous);$this->assertNull($report->reporter_name);$this->assertNull($report->reporter_contact);
        $this->asActor($this->root())->get('/pta/pengaduan/'.$report->reference)->assertOk()->assertDontSee($actor->email)->assertDontSee($actor->phone)->assertDontSee($actor->name);
        $this->get('/pengaduan/lacak')->assertSee('tidak dapat dikirim ulang atau dipulihkan');
    }
    public function test_pta_updates_are_audited_encrypted_and_internal_notes_never_reach_reporter()
    {
        $report=$this->submit();$root=$this->root();
        $this->asActor($root)->post('/pta/pengaduan/'.$report->reference,['status'=>'reviewing','lock_version'=>1,
            'public_message'=>'Pesan untuk pelapor pengujian','internal_note'=>'CATATAN INTERNAL SANGAT PRIVAT'])->assertRedirect();
        $report->refresh();$this->assertSame('reviewing',$report->status);$this->assertSame(2,$report->lock_version);$this->assertSame($root->id,$report->assigned_to);
        $raw=DB::table('complaint_events')->orderByDesc('id')->first();$this->assertStringNotContainsString('SANGAT PRIVAT',$raw->internal_note);
        $this->get('/pta/pengaduan/'.$report->reference)->assertOk()->assertSee('CATATAN INTERNAL SANGAT PRIVAT');
        $this->get('/pengaduan/tiket/'.$report->reference)->assertOk()->assertSee('Pesan untuk pelapor pengujian')->assertDontSee('CATATAN INTERNAL SANGAT PRIVAT');
    }
    public function test_concurrent_updates_require_fresh_version_and_completion_can_be_reopened()
    {
        $report=$this->submit();$this->asActor($this->root());
        $this->post('/pta/pengaduan/'.$report->reference,['status'=>'completed','lock_version'=>1,'public_message'=>'Penanganan pengujian selesai'])->assertRedirect();
        $this->post('/pta/pengaduan/'.$report->reference,['status'=>'follow_up','lock_version'=>1,'public_message'=>'Pembaruan kedaluwarsa'])->assertSessionHasErrors('lock_version');
        $this->assertSame(2,ComplaintEvent::count());$this->assertSame('completed',$report->fresh()->status);
        $this->post('/pta/pengaduan/'.$report->reference,['status'=>'reviewing','lock_version'=>2,'public_message'=>'Ditelaah kembali untuk pengujian'])->assertRedirect();
        $this->assertSame('reviewing',$report->fresh()->status);$this->assertSame(3,ComplaintEvent::count());
    }
    public function test_pta_can_appoint_handler_and_revocation_ends_old_session()
    {
        $root=$this->root();$handler=$this->actor(['work_unit'=>config('ptsp.supervising_work_unit')]);
        $this->asActor($root)->put('/admin/accounts/'.$handler->id,$this->accountPayload($handler,['can_handle_complaints'=>1]))->assertRedirect();
        $this->assertTrue($handler->fresh()->canHandleComplaints());$this->assertSame(2,$handler->fresh()->auth_version);
        $this->asActor($handler->fresh())->get('/pta/pengaduan')->assertOk();
        $this->asActor($root)->put('/admin/accounts/'.$handler->id,$this->accountPayload($handler,['can_handle_complaints'=>0]))->assertRedirect();
        $this->assertFalse($handler->fresh()->canHandleComplaints());
        $this->actingAs($handler->fresh())->withSession(['auth_version'=>2])->getJson('/pta/pengaduan')->assertUnauthorized();
    }
    public function test_outside_admin_cannot_self_appoint_reset_pta_password_or_create_pta_root()
    {
        $root=$this->root();$outside=$this->actor(['role'=>'admin','can_monitor'=>true]);$handler=$this->actor(['work_unit'=>config('ptsp.supervising_work_unit')]);
        $this->asActor($outside)->put('/admin/accounts/'.$handler->id,$this->accountPayload($handler,['can_handle_complaints'=>1]))->assertForbidden();
        $this->put('/admin/accounts/'.$outside->id,$this->accountPayload($outside,['work_unit'=>config('ptsp.supervising_work_unit')]))->assertForbidden();
        $this->put('/admin/accounts/'.$root->id,$this->accountPayload($root,['password'=>'NewSecretPassword!','password_confirmation'=>'NewSecretPassword!']))->assertForbidden();
        $this->get('/admin/accounts/'.$root->id.'/edit')->assertForbidden();
        $this->assertFalse($outside->fresh()->canHandleComplaints());
    }
    public function test_pta_assignment_must_stay_in_pta_and_last_pta_admin_cannot_be_removed()
    {
        $root=$this->root();$outside=$this->actor();$this->asActor($root);
        $this->put('/admin/accounts/'.$outside->id,$this->accountPayload($outside,['can_handle_complaints'=>1]))->assertSessionHasErrors('can_handle_complaints');
        $this->put('/admin/accounts/'.$root->id,$this->accountPayload($root,['work_unit'=>'Pengadilan Agama Sorong']))->assertSessionHasErrors('is_active');
        $this->assertTrue($root->fresh()->canManageComplaintAccess());
    }
    public function test_filters_are_scoped_and_content_is_html_escaped()
    {
        $report=$this->submit(['subject'=>'<script>subjectBad()</script>','description'=>'<script>bodyBad()</script> Penjelasan panjang untuk pengujian.']);
        $this->get('/pengaduan/tiket/'.$report->reference)->assertOk()->assertDontSee('<script>subjectBad()',false)->assertSee('&lt;script&gt;subjectBad()',false);
        $this->asActor($this->root())->get('/pta/pengaduan?type=gratification')->assertOk()->assertDontSee($report->reference);
        $this->get('/pta/pengaduan?q='.$report->reference)->assertOk()->assertSee($report->reference)->assertDontSee('subjectBad');
    }
    public function test_file_storage_failure_rolls_back_report_and_keeps_input()
    {
        $disk=\Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);$disk->shouldReceive('delete')->once()->andReturn(true);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $this->from('/pengaduan')->post('/pengaduan',$this->payload(['evidence'=>[UploadedFile::fake()->create('proof.pdf',1,'application/pdf')]]))
            ->assertSessionHasErrors('submission')->assertSessionHasInput('subject','Judul laporan pengujian');
        $this->assertSame(0,Complaint::count());$this->assertSame(0,ComplaintAttachment::count());
    }
    public function test_tracking_attempts_are_bounded_and_then_recover()
    {
        for($i=0;$i<10;$i++)$this->from('/pengaduan/lacak')->post('/pengaduan/lacak',['reference'=>'ADU-20261102-ABCDEFGH','access_code'=>'invalid'])->assertSessionHasErrors('access_code');
        $this->post('/pengaduan/lacak',['reference'=>'ADU-20261102-ABCDEFGH','access_code'=>'invalid'])->assertSessionHasErrors('rate_limit');
        Carbon::setTestNow(now()->addSeconds(61));
        $this->post('/pengaduan/lacak',['reference'=>'ADU-20261102-ABCDEFGH','access_code'=>'invalid'])->assertSessionHasErrors('access_code');
    }

    public function test_disabled_and_guest_principals_cannot_read_even_with_assignment_flag()
    {
        $disabled=$this->actor(['work_unit'=>config('ptsp.supervising_work_unit'),'can_handle_complaints'=>true]);
        $disabled->forceFill(['is_active'=>false])->save();
        $this->asActor($disabled)->getJson('/pta/pengaduan')->assertUnauthorized();
        $guest=$this->actor(['role'=>'admin','can_monitor'=>true,'work_unit'=>config('ptsp.supervising_work_unit'),'can_handle_complaints'=>true]);
        $guest->forceFill(['is_guest'=>true])->save();
        $this->asActor($guest)->get('/pta/pengaduan')->assertForbidden();
    }

    public function test_client_cannot_set_workflow_permissions_or_internal_notes_on_submission()
    {
        $report=$this->submit(['status'=>'completed','assigned_to'=>999,'can_handle_complaints'=>1,'internal_note'=>'INJECTED INTERNAL NOTE']);
        $this->assertSame('received',$report->status);$this->assertNull($report->assigned_to);
        $this->assertNull($report->events()->first()->internal_note);
    }

    public function test_corrupt_evidence_returns_generic_error_without_exposing_contents()
    {
        $report=$this->submit(['evidence'=>[UploadedFile::fake()->create('proof.pdf',1,'application/pdf')]]);
        $file=$report->attachments()->firstOrFail();Storage::disk('local')->put($file->path,'CORRUPT PRIVATE CONTENT');
        $this->get('/pengaduan/tiket/'.$report->reference.'/bukti/'.$file->id)->assertStatus(503)->assertDontSee('CORRUPT PRIVATE CONTENT');
    }

    public function test_inbox_paginates_without_exposing_subjects_or_reporter_identity()
    {
        for($i=0;$i<16;$i++)Complaint::create(['reference'=>'ADU-20261102-'.sprintf('%08d',$i),'request_hash'=>hash('sha256','request-'.$i),
            'access_hash'=>hash('sha256','code-'.$i),'work_unit_id'=>$this->unit()->id,'type'=>'service','occurred_on'=>'2026-11-01',
            'subject'=>'SUBJECT PRIVATE','description'=>'DESCRIPTION PRIVATE','reporter_name'=>'REPORTER PRIVATE','status'=>'received','consented_at'=>now()]);
        $this->asActor($this->root())->get('/pta/pengaduan')->assertOk()->assertSee('1–15 dari 16')->assertDontSee('SUBJECT PRIVATE')->assertDontSee('REPORTER PRIVATE');
        $this->get('/pta/pengaduan?page=2')->assertOk()->assertSee('16–16 dari 16');
    }

    public function test_reporter_can_close_device_access_without_deleting_report()
    {
        $report=$this->submit();$secret=session('complaint_receipt.secret');
        $this->post('/pengaduan/tiket/'.$report->reference.'/tutup')->assertRedirect(route('public.complaints.track'));
        $this->get('/pengaduan/tiket/'.$report->reference)->assertNotFound();$this->assertSame(1,Complaint::count());
        $this->post('/pengaduan/lacak',['reference'=>$report->reference,'access_code'=>$secret])->assertRedirect();
        $this->get('/pengaduan/tiket/'.$report->reference)->assertOk();
    }

    public function test_legacy_complaint_service_routes_to_pta_without_opening_local_chat()
    {
        $service=\App\Service::create(['name'=>'Pengaduan layanan','description'=>'Laporan layanan','category'=>'pengaduan','icon'=>'fa-bullhorn','is_active'=>true]);
        $this->asActor($this->actor(['role'=>'user']))->get('/services')->assertOk()->assertSee('Kirim pengaduan ke PTA');
        $this->post('/chat/create-room',['service_id'=>$service->id])->assertRedirect(route('public.complaints.create'));
        $this->assertSame(0,\App\ChatRoom::count());
    }

    public function test_disabling_consultation_intake_does_not_block_complaints_about_satker()
    {
        $this->unit()->update(['is_active'=>false]);
        $report=$this->submit();$this->assertSame($this->unit()->id,$report->work_unit_id);
    }
}
