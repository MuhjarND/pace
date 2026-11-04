<?php

namespace Tests\Feature;

use App\AdminActivityLog;
use App\ConsultationSetting;
use App\Meeting;
use App\ServiceHoliday;
use App\ServiceHour;
use App\User;
use App\WorkUnit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PtspOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('hashing.bcrypt.rounds', 4);
        return $app;
    }
    protected function setUp(): void { parent::setUp(); Carbon::setTestNow(Carbon::parse('2026-10-27 00:00:00', 'UTC')); }
    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }
    private function account($data = []) { return factory(User::class)->create(array_merge(['role'=>'user', 'work_unit'=>'Pengadilan Agama Sorong'], $data)); }
    private function admin() { return $this->account(['role'=>'admin', 'can_monitor'=>true]); }
    private function staff($unit = 'Pengadilan Agama Sorong') { return $this->account(['role'=>'operator', 'work_unit'=>$unit]); }
    private function payload($extra = []) { return array_merge(['name'=>'Petugas baru', 'email'=>'petugas.baru@example.test', 'phone'=>null, 'role'=>'operator', 'work_unit'=>'Pengadilan Agama Sorong', 'is_active'=>'1', 'can_monitor'=>'0', 'password'=>'ContohAkunBaru!2026', 'password_confirmation'=>'ContohAkunBaru!2026'], $extra); }
    private function setting($unit = 'Pengadilan Agama Sorong') { ConsultationSetting::firstOrCreate(['work_unit'=>$unit], ['join_url'=>'https://meet.google.com/hrg-wbdh-xvg']); }
    private function book($actor, $extra = []) { return $this->actingAs($actor)->post('/meetings', array_merge(['consultation_date'=>'2026-10-27', 'consultation_time'=>'now', 'topic'=>'Konsultasi pengujian'], $extra)); }
    private function hours($extra = []) {
        $days = [];
        foreach (range(0, 6) as $day) { $days[$day] = ['is_open'=>'1', 'opens_at'=>'08:00', 'closes_at'=>'16:00', 'break_start'=>'12:00', 'break_end'=>'13:00']; }
        return array_merge(['schedule_enabled'=>'1', 'daily_quota'=>'2', 'hours'=>$days], $extra);
    }

    public function test_only_central_admin_can_manage_accounts_and_units()
    {
        foreach ([$this->account(), $this->staff(), $this->account(['role'=>'operator', 'can_monitor'=>true]), $this->account(['role'=>'admin', 'can_monitor'=>false])] as $actor) {
            $this->actingAs($actor)->get('/admin/accounts')->assertForbidden();
            $this->post('/admin/accounts', $this->payload())->assertForbidden();
            $this->post('/admin/work-units', ['name'=>'Tidak boleh'])->assertForbidden();
        }
        $this->actingAs($this->admin())->get('/admin/accounts')->assertOk();
        $this->get('/admin/accounts/create')->assertOk()->assertSee('Tambah akun pengguna');
        $this->get('/admin/work-units')->assertOk()->assertSee('Pengadilan Agama Sorong');
    }

    public function test_account_creation_hashes_password_and_records_only_safe_audit_fields()
    {
        $this->actingAs($this->admin())->post('/admin/accounts', $this->payload())->assertRedirect();
        $created = User::where('email', 'petugas.baru@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('ContohAkunBaru!2026', $created->password));
        $this->assertTrue($created->is_active);
        $this->assertSame('operator', $created->role);
        $this->assertFalse($created->can_monitor);
        $log = AdminActivityLog::firstOrFail();
        $this->assertTrue($log->changes['password_changed']);
        $this->assertStringNotContainsString('ContohAkunBaru!', json_encode($log->changes));
        $this->assertStringNotContainsString($created->password, json_encode($log->changes));
        $this->get('/admin/accounts/'.$created->id.'/edit')->assertOk()->assertDontSee($created->password);
    }

    public function test_cannot_disable_or_demote_current_central_admin()
    {
        $root = $this->admin();
        $data = $this->payload(['email'=>$root->email, 'name'=>$root->name, 'role'=>'admin', 'can_monitor'=>'1', 'is_active'=>'0', 'password'=>null, 'password_confirmation'=>null]);
        $this->actingAs($root)->put('/admin/accounts/'.$root->id, $data)->assertSessionHasErrors('is_active');
        $this->assertTrue($root->fresh()->is_active);
        $this->assertTrue($root->fresh()->canManageMasterData());
    }

    public function test_inactive_account_cannot_login_by_password_or_phone_or_keep_a_session()
    {
        $user = $this->account(['phone'=>'6281000031001', 'password'=>Hash::make('AkunNonaktif!2026')]);
        $user->forceFill(['is_active'=>false])->save();
        $this->post('/login', ['email'=>$user->email, 'password'=>'AkunNonaktif!2026'])->assertSessionHasErrors('email');
        $this->post('/welcome/check', ['phone'=>$user->phone, 'work_unit'=>$user->work_unit])->assertRedirect(route('public.consult'));
        $this->assertGuest();
        $this->actingAs($user)->getJson('/notifications/fetch')->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_changed_password_and_permissions_revoke_existing_sessions()
    {
        $root = $this->admin(); $staff = $this->staff();
        $data = $this->payload(['email'=>$staff->email, 'name'=>$staff->name]);
        $this->actingAs($root)->put('/admin/accounts/'.$staff->id, $data)->assertRedirect();
        $this->assertSame(2, $staff->fresh()->auth_version);
        $this->actingAs($staff->fresh())->withSession(['auth_version'=>1])->getJson('/operations/queue/data')->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_dynamic_satker_can_be_used_immediately_for_accounts_registration_and_routing()
    {
        $root = $this->admin();
        $this->actingAs($root)->post('/admin/work-units', ['name'=>'Satker uji dinamis', 'code'=>'QA-001', 'is_active'=>'1'])->assertRedirect();
        $this->post('/admin/accounts', $this->payload(['work_unit'=>'Satker uji dinamis']))->assertRedirect();
        $staff = User::where('email', 'petugas.baru@example.test')->firstOrFail();
        auth()->logout(); session()->forget('auth_version');
        $this->get('/konsultasi')->assertSee('Satker uji dinamis');
        $this->post('/konsultasi', ['name'=>'Pendaftar satker baru','work_unit_id'=>WorkUnit::where('name','Satker uji dinamis')->value('id'),'channel'=>'chat','topic'=>'Pertanyaan uji','consent'=>'1','request_key'=>session('public_booking_nonce')])->assertRedirect();
        $this->assertDatabaseHas('chat_rooms', ['work_unit'=>'Satker uji dinamis', 'admin_id'=>$staff->id]);
        $this->assertNotContains('Satker uji dinamis', config('ptsp.work_units'));
    }

    public function test_inactive_satker_blocks_new_registration_and_booking_but_preserves_existing_login_and_history()
    {
        $this->setting(); $citizen = $this->account(['phone'=>'6281000031003']);
        $this->book($citizen)->assertRedirect(); $meeting = Meeting::firstOrFail();
        WorkUnit::where('name', $citizen->work_unit)->update(['is_active'=>false]);
        $this->actingAs($this->account())->post('/meetings', ['topic'=>'Baru', 'consultation_date'=>'2026-10-27', 'consultation_time'=>'now'])->assertSessionHasErrors('consultation_time');
        auth()->logout(); session()->forget('auth_version');
        $this->get('/konsultasi')->assertOk();
        $this->post('/konsultasi',['name'=>'Baru','work_unit_id'=>WorkUnit::where('name',$citizen->work_unit)->value('id'),'channel'=>'chat','topic'=>'Pertanyaan uji','consent'=>'1','request_key'=>session('public_booking_nonce')])->assertStatus(422);
        $this->post('/welcome/check', ['phone'=>$citizen->phone, 'work_unit'=>$citizen->work_unit])->assertRedirect(route('public.consult'));
        $this->assertGuest();
        $this->actingAs($citizen);
        $this->get('/meetings')->assertSee($meeting->topic);
        $this->actingAs($this->staff())->post('/meetings/'.$meeting->id.'/complete')->assertRedirect();
        $this->assertSame('ended', $meeting->fresh()->status);
    }

    public function test_hours_apply_only_to_own_unit_and_validate_intervals()
    {
        $this->actingAs($this->account())->get('/operations/hours')->assertForbidden();
        $staff = $this->staff();
        $this->actingAs($staff)->get('/operations/hours')->assertOk()->assertSee('Jam layanan satker');
        $invalid = $this->hours(); $invalid['hours'][2]['break_end'] = '11:00';
        $this->put('/operations/hours', $invalid)->assertSessionHasErrors('hours.2.break_start');
        $data = $this->hours(['work_unit'=>'Pengadilan Agama Manokwari']);
        $this->put('/operations/hours', $data)->assertRedirect();
        $this->assertTrue(WorkUnit::where('name', $staff->work_unit)->firstOrFail()->schedule_enabled);
        $this->assertFalse(WorkUnit::where('name', 'Pengadilan Agama Manokwari')->firstOrFail()->schedule_enabled);
        $this->assertSame(7, ServiceHour::count());
    }

    public function test_availability_uses_wit_and_excludes_break_and_closing_boundary()
    {
        $this->setting(); $this->actingAs($this->staff())->put('/operations/hours', $this->hours())->assertRedirect();
        $citizen = $this->account();
        $result = $this->actingAs($citizen)->getJson('/meetings/availability?date=2026-10-27')->assertOk()->assertJsonPath('can_now', true)->assertJsonPath('timezone', 'WIT')->json();
        $this->assertContains('09:00', $result['slots']);
        $this->assertContains('11:45', $result['slots']);
        $this->assertNotContains('12:00', $result['slots']); $this->assertNotContains('12:45', $result['slots']);
        $this->assertContains('13:00', $result['slots']); $this->assertNotContains('16:00', $result['slots']);
        $this->book($citizen, ['consultation_time'=>'12:15'])->assertSessionHasErrors('consultation_time');
        $this->book($citizen, ['consultation_time'=>'16:00'])->assertSessionHasErrors('consultation_time');
        $this->book($citizen, ['consultation_time'=>'09:07'])->assertSessionHasErrors('consultation_time');
        $this->assertSame(0, Meeting::count());
    }

    public function test_holidays_close_new_bookings_and_other_units_cannot_remove_them()
    {
        $this->setting(); $staff = $this->staff();
        $this->actingAs($staff)->put('/operations/hours', $this->hours())->assertRedirect();
        $this->post('/operations/holidays', ['date'=>'2026-10-28', 'name'=>'Hari tutup uji'])->assertRedirect();
        $holiday = ServiceHoliday::firstOrFail();
        $this->post('/operations/holidays', ['date'=>'2026-10-28', 'name'=>'Duplikat'])->assertSessionHasErrors('date');
        $citizen = $this->account();
        $this->actingAs($citizen)->getJson('/meetings/availability?date=2026-10-28')->assertJsonPath('slots', [])->assertJsonPath('can_now', false);
        $this->book($citizen, ['consultation_date'=>'2026-10-28', 'consultation_time'=>'10:00'])->assertSessionHasErrors('consultation_time');
        $this->actingAs($this->staff('Pengadilan Agama Manokwari'))->delete('/operations/holidays/'.$holiday->id)->assertNotFound();
        $this->actingAs($staff)->delete('/operations/holidays/'.$holiday->id)->assertRedirect();
        $this->actingAs($citizen)->getJson('/meetings/availability?date=2026-10-28')->assertJsonCount(28, 'slots');
    }

    public function test_daily_quota_is_enforced_and_cancellation_frees_capacity()
    {
        $this->setting(); $staff = $this->staff();
        $this->actingAs($staff)->put('/operations/hours', $this->hours(['daily_quota'=>'1']))->assertRedirect();
        $first = $this->account(); $second = $this->account();
        $future = ['consultation_date'=>'2026-10-28', 'consultation_time'=>'10:00'];
        $this->book($first, $future)->assertRedirect();
        $meeting = Meeting::firstOrFail();
        $this->book($first, $future)->assertRedirect();
        $this->assertSame(1, Meeting::count());
        $this->book($second, $future)->assertSessionHasErrors('consultation_time');
        $this->getJson('/meetings/availability?date=2026-10-28')->assertJsonPath('quota_remaining', 0)->assertJsonPath('slots', []);
        $this->actingAs($first)->delete('/meetings/'.$meeting->id)->assertRedirect();
        $this->book($second, $future)->assertRedirect();
        $this->assertSame(2, Meeting::count());
        $this->assertSame(2, Meeting::latest('id')->first()->queue_number);
    }

    public function test_existing_booking_can_be_completed_after_hours_are_changed()
    {
        $this->setting(); $first = $this->account(); $this->book($first)->assertRedirect();
        $meeting = Meeting::firstOrFail();
        $this->actingAs($this->staff())->post('/meetings/'.$meeting->id.'/start')->assertRedirect();
        $started = $meeting->fresh()->started_at;
        $staff = $this->staff(); $data = $this->hours(); $data['hours'][2]['is_open'] = '0';
        $this->actingAs($staff)->put('/operations/hours', $data)->assertRedirect();
        $this->post('/meetings/'.$meeting->id.'/complete', ['queue_return'=>'1'])->assertRedirect(route('operations.queue'));
        $this->assertSame('ended', $meeting->fresh()->status);
        $this->assertNotNull($started);
        $this->assertTrue($started->equalTo($meeting->fresh()->started_at));
        $this->book($this->account())->assertSessionHasErrors('consultation_time');
    }

    public function test_queue_dashboard_scopes_records_and_monitoring_is_read_only()
    {
        $this->setting(); $this->setting('Pengadilan Agama Manokwari');
        $sorong = $this->account(['name'=>'Pemohon Sorong']); $manokwari = $this->account(['name'=>'Pemohon Manokwari', 'work_unit'=>'Pengadilan Agama Manokwari']);
        $this->book($sorong)->assertRedirect(); $this->book($manokwari, ['topic'=>'Topik hanya Manokwari'])->assertRedirect();
        $this->actingAs($sorong)->get('/operations/queue')->assertForbidden();
        $this->getJson('/operations/queue/data')->assertForbidden();
        $staff = $this->staff();
        $this->actingAs($staff)->get('/operations/queue')->assertOk()->assertSee('Pemohon Sorong')->assertDontSee('Topik hanya Manokwari');
        $this->getJson('/operations/queue/data?work_unit=Pengadilan%20Agama%20Manokwari')->assertForbidden();
        $root = $this->admin();
        $this->actingAs($root)->get('/operations/queue?work_unit=Pengadilan%20Agama%20Manokwari')->assertOk()->assertSee('Topik hanya Manokwari')->assertSee('Monitoring baca saja')->assertDontSee('name="queue_return"', false);
        $other = Meeting::where('work_unit', 'Pengadilan Agama Manokwari')->firstOrFail();
        $this->post('/meetings/'.$other->id.'/complete', ['queue_return'=>'1'])->assertForbidden();
    }

    public function test_queue_refresh_fragment_escapes_content_and_keeps_pending_and_active_separate()
    {
        $this->setting(); $citizen = $this->account(['name'=>'<script>alert(1)</script>']);
        $this->book($citizen, ['consultation_date'=>'2026-10-28', 'consultation_time'=>'10:00', 'topic'=>'<img src=x onerror=alert(2)>'])->assertRedirect();
        $staff = $this->staff();
        $activeHtml = $this->actingAs($staff)->getJson('/operations/queue/data?tab=active')->assertOk()->json('html');
        $this->assertStringNotContainsString('&lt;script&gt;', $activeHtml);
        $this->get('/operations/queue')->assertViewHas('summary', function ($counts) { return $counts['pending'] === 1 && $counts['waiting'] === 0 && $counts['serving'] === 0; });
        $html = $this->getJson('/operations/queue/data?tab=pending')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('html');
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('name="decision" value="verified"', $html);
        $this->get('/operations/queue?date=not-a-date')->assertSessionHasErrors('date');
    }

    public function test_dashboard_totals_follow_the_same_account_and_unit_scope()
    {
        $this->setting(); $this->setting('Pengadilan Agama Manokwari');
        $citizen = $this->account();
        $otherCitizen = $this->account(['work_unit'=>'Pengadilan Agama Manokwari']);
        $this->book($citizen, ['consultation_date'=>'2026-10-28', 'consultation_time'=>'10:00'])->assertRedirect();
        $this->book($otherCitizen, ['consultation_date'=>'2026-10-28', 'consultation_time'=>'10:00'])->assertRedirect();
        $staff = $this->staff(); $otherStaff = $this->staff('Pengadilan Agama Manokwari');
        \App\CallLog::create(['caller_id'=>$citizen->id, 'receiver_id'=>$staff->id]);
        \App\CallLog::create(['caller_id'=>$otherCitizen->id, 'receiver_id'=>$otherStaff->id]);
        $this->actingAs($citizen)->get('/dashboard')->assertViewHas('totalMeetings', 1)->assertViewHas('totalCalls', 1);
        $this->actingAs($staff)->get('/dashboard')->assertViewHas('totalMeetings', 1)->assertViewHas('totalCalls', 1);
        $this->actingAs($this->admin())->get('/dashboard')->assertViewHas('totalMeetings', 2)->assertViewHas('totalCalls', 2);
    }
}
