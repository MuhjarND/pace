<?php
namespace Tests\Feature;

use App\ChatRoom;
use App\ConsultationSetting;
use App\Meeting;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleMeetTest extends TestCase
{
    use RefreshDatabase;
    public function createApplication() {
        $app=parent::createApplication();
        $app['config']->set('database.default','sqlite');
        $app['config']->set('database.connections.sqlite.database',':memory:');
        return $app;
    }
    protected function setUp(): void { parent::setUp(); Carbon::setTestNow(Carbon::parse('2026-10-27 00:00:00','UTC')); }
    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }
    private function user(array $data=[]) { return factory(User::class)->create(array_merge(['role'=>'user','work_unit'=>'Pengadilan Agama Sorong'],$data)); }
    private function staff(array $data=[]) { return $this->user(array_merge(['role'=>'operator'],$data)); }
    private function setting($unit='Pengadilan Agama Sorong') { return ConsultationSetting::firstOrCreate(['work_unit'=>$unit],['join_url'=>'https://meet.google.com/hrg-wbdh-xvg']); }
    private function book(User $user,array $data=[]) {
        return $this->actingAs($user)->post('/meetings',array_merge(['consultation_date'=>'2026-10-27','consultation_time'=>'now','topic'=>'Topik rahasia uji'],$data));
    }

    public function test_link_alone_enables_self_scheduling_without_staff_slots() {
        $staff=$this->staff(); $this->actingAs($staff)->post('/meetings/settings',['join_url'=>'https://meet.google.com/meet/hrg-wbdh-xvg'])->assertRedirect();
        $this->get('/meetings/manage')->assertOk()->assertSee('Tautan Tetap')->assertDontSee('Terbitkan Jadwal');
        $user=$this->user(); $this->actingAs($user)->get('/meetings/create')->assertOk()->assertSee('Tanggal Konsultasi')->assertSee('Waktu Konsultasi')->assertSee('Antrean Satker')->assertDontSee('name="join_url"',false);
        $this->book($user)->assertRedirect('https://meet.google.com/hrg-wbdh-xvg');
        $meeting=Meeting::firstOrFail(); $this->assertSame('scheduled',$meeting->status); $this->assertSame('A001',$meeting->queueLabel()); $this->assertNull($meeting->slot_id);
    }

    public function test_queue_notice_allows_join_but_does_not_falsely_start_next_service() {
        $this->setting(); $staff=$this->staff(); $first=$this->user(); $second=$this->user();
        $this->book($first)->assertRedirect('https://meet.google.com/hrg-wbdh-xvg');
        $this->book($second)->assertRedirect();
        $head=Meeting::where('user_id',$first->id)->firstOrFail(); $next=Meeting::where('user_id',$second->id)->firstOrFail();
        $this->get('/meetings/'.$next->id.'/join')->assertOk()->assertSee('Menunggu giliran')->assertSee('A002');
        $this->getJson('/meetings/'.$next->id.'/queue')->assertOk()->assertJsonPath('ahead',1)->assertJsonPath('position',2)->assertJsonMissing(['name'=>$first->name])->assertJsonMissing(['topic'=>'Topik rahasia uji']);
        $this->post('/meetings/'.$next->id.'/enter')->assertRedirect('https://meet.google.com/hrg-wbdh-xvg');
        $this->assertSame('scheduled',$next->fresh()->status);
        $this->actingAs($staff)->post('/meetings/'.$next->id.'/start')->assertStatus(422);
        $this->post('/meetings/'.$next->id.'/complete')->assertStatus(422);
        $this->post('/meetings/'.$head->id.'/complete')->assertRedirect();
        $this->actingAs($second)->getJson('/meetings/'.$next->id.'/queue')->assertJsonPath('ready',true)->assertJsonPath('ahead',0);
        $this->get('/meetings/'.$next->id.'/join')->assertRedirect('https://meet.google.com/hrg-wbdh-xvg');
        $this->assertSame('scheduled',$next->fresh()->status);
    }

    public function test_future_schedules_share_time_and_require_verification() {
        $this->setting(); $staff=$this->staff(); $one=$this->user(); $two=$this->user();
        $data=['consultation_date'=>'2026-10-28','consultation_time'=>'10:00'];
        $this->book($one,$data)->assertRedirect(route('meetings.index')); $this->book($two,$data)->assertRedirect();
        $this->assertSame(2,Meeting::count()); $meeting=Meeting::where('user_id',$one->id)->firstOrFail();
        $this->assertSame('2026-10-28 01:00',$meeting->start_time->format('Y-m-d H:i'));
        $this->assertSame('pending',$meeting->verification_status);
        $this->actingAs($one)->get('/meetings')->assertSee('Belum diverifikasi');
        $this->get('/meetings/'.$meeting->id.'/join')->assertForbidden();
        $this->actingAs($staff)->getJson('/notifications/fetch')->assertOk()->assertJsonPath('unread_meetings',2);
        $this->post('/meetings/'.$meeting->id.'/verify',['decision'=>'verified'])->assertRedirect();
        $this->actingAs($one)->get('/meetings/'.$meeting->id.'/join')->assertForbidden();
        Carbon::setTestNow('2026-10-28 01:00:00');
        $this->get('/meetings/'.$meeting->id.'/join')->assertRedirect('https://meet.google.com/hrg-wbdh-xvg');
    }

    public function test_same_user_duplicate_submission_reuses_number_and_same_time_does_not_duplicate() {
        $this->setting(); $user=$this->user(); $this->book($user); $this->book($user);
        $this->assertSame(1,Meeting::count());
        $future=['consultation_date'=>'2026-10-28','consultation_time'=>'11:00'];
        $this->book($user,$future); $this->book($user,$future); $this->assertSame(2,Meeting::count());
    }

    public function test_queue_is_isolated_per_unit_and_numbers_are_daily() {
        $this->setting(); $this->setting('Pengadilan Agama Manokwari');
        $this->book($this->user())->assertRedirect('https://meet.google.com/hrg-wbdh-xvg');
        $other=$this->user(['work_unit'=>'Pengadilan Agama Manokwari']); $this->book($other)->assertRedirect('https://meet.google.com/hrg-wbdh-xvg');
        $this->assertSame(1,Meeting::where('user_id',$other->id)->first()->queue_number);
        $next=$this->user(); $this->book($next,['consultation_date'=>'2026-10-28','consultation_time'=>'09:00']);
        $this->assertSame(1,Meeting::where('user_id',$next->id)->first()->queue_number);
    }

    public function test_only_local_staff_can_start_complete_and_verify_and_public_cannot_access_others_queue() {
        $this->setting(); $user=$this->user(); $this->book($user); $meeting=Meeting::firstOrFail();
        $this->post('/meetings/'.$meeting->id.'/complete')->assertForbidden();
        $this->actingAs($this->user())->getJson('/meetings/'.$meeting->id.'/queue')->assertNotFound();
        $this->post('/meetings/'.$meeting->id.'/enter')->assertNotFound();
        $this->actingAs($this->staff(['work_unit'=>'Pengadilan Agama Manokwari']))->post('/meetings/'.$meeting->id.'/complete')->assertNotFound();
        $this->actingAs($this->staff(['work_unit'=>'Pengadilan Tinggi Agama Papua Barat','can_monitor'=>true]))->post('/meetings/'.$meeting->id.'/complete')->assertForbidden();
        $this->actingAs($this->staff())->post('/meetings/'.$meeting->id.'/complete')->assertRedirect();
        $this->assertSame('ended',$meeting->fresh()->status);
        $this->actingAs($user)->get('/meetings')->assertSee('Selesai'); $this->get('/meetings/'.$meeting->id.'/join')->assertForbidden();
    }

    public function test_cancellation_advances_queue_without_reusing_number() {
        $this->setting(); $one=$this->user(); $two=$this->user(); $this->book($one); $first=Meeting::firstOrFail(); $this->book($two);
        $this->actingAs($one)->delete('/meetings/'.$first->id)->assertRedirect();
        $next=Meeting::where('user_id',$two->id)->firstOrFail();
        $this->actingAs($two)->getJson('/meetings/'.$next->id.'/queue')->assertJsonPath('ready',true);
        $this->book($this->user())->assertRedirect(); $this->assertSame(3,(int) Meeting::max('queue_number'));
    }

    public function test_started_service_is_not_displaced_by_an_earlier_schedule_verified_late() {
        $this->setting(); $staff=$this->staff();
        $scheduled=$this->user(); $this->book($scheduled,['consultation_date'=>'2026-10-27','consultation_time'=>'10:00']);
        Carbon::setTestNow('2026-10-27 02:00:00');
        $current=$this->user(); $this->book($current)->assertRedirect();
        $active=Meeting::where('user_id',$current->id)->firstOrFail();
        $this->actingAs($staff)->post('/meetings/'.$active->id.'/start')->assertRedirect();
        $older=Meeting::where('user_id',$scheduled->id)->firstOrFail();
        $this->actingAs($staff)->post('/meetings/'.$older->id.'/verify',['decision'=>'verified'])->assertRedirect();
        $this->actingAs($scheduled)->getJson('/meetings/'.$older->id.'/queue')->assertJsonPath('ahead',1);
    }

    public function test_link_changes_apply_to_active_bookings_and_cannot_be_changed_by_citizens() {
        $this->setting(); $user=$this->user(); $this->book($user);
        $this->post('/meetings/settings',['join_url'=>'https://meet.google.com/abc-defg-hij'])->assertForbidden();
        $this->actingAs($this->staff())->post('/meetings/settings',['join_url'=>'https://meet.google.com/abc-defg-hij'])->assertRedirect();
        $this->assertSame('https://meet.google.com/abc-defg-hij',Meeting::firstOrFail()->join_url);
    }

    /** @dataProvider invalidLinks */
    public function test_invalid_settings_links_are_rejected($url) {
        $this->actingAs($this->staff())->postJson('/meetings/settings',['join_url'=>$url])->assertStatus(422)->assertJsonValidationErrors('join_url');
    }
    public function invalidLinks() { return [[null],['https://zoom.us/j/1234567890'],['https://meet.google.com.evil.example/abc-defg-hij'],['javascript:alert(1)'],['http://meet.google.com/abc-defg-hij']]; }

    public function test_past_scheduling_missing_link_and_false_now_dates_are_rejected() {
        $user=$this->user(); $this->book($user)->assertSessionHasErrors('consultation_date'); $this->setting();
        $this->book($user,['consultation_date'=>'2026-10-26'])->assertSessionHasErrors('consultation_date');
        $this->book($user,['consultation_date'=>'2026-10-28'])->assertSessionHasErrors('consultation_date');
        $this->book($user,['consultation_time'=>'08:00'])->assertSessionHasErrors('consultation_time');
        $this->assertSame(0,Meeting::count());
    }

    public function test_staff_voice_page_selects_local_conversations() {
        $staff=$this->staff(); $this->actingAs($staff)->get('/chat/voice-mode')->assertOk()->assertSee('Belum ada percakapan');
        $user=$this->user(); $room=ChatRoom::create(['user_id'=>$user->id,'work_unit'=>$staff->work_unit,'status'=>'open']);
        $this->get('/chat/voice-mode?room_id='.$room->id)->assertOk()->assertSee($user->name);
        $this->postJson('/chat/send',['chat_room_id'=>$room->id,'message'=>'Dikte balasan'])->assertOk();
    }
}
