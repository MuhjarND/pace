<?php

namespace Tests\Feature;

use App\ChatMessage;
use App\ChatRoom;
use App\Service;
use App\Services\ChatRoutingService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        return $app;
    }

    private function citizen($unit = 'Pengadilan Agama Sorong', array $attributes = [])
    {
        return factory(User::class)->create(array_merge(['role' => 'user', 'work_unit' => $unit], $attributes));
    }

    private function staff($unit = 'Pengadilan Agama Sorong', array $attributes = [])
    {
        return $this->citizen($unit, array_merge(['role' => 'operator'], $attributes));
    }

    private function room(User $user)
    {
        return app(ChatRoutingService::class)->openFor($user);
    }

    /** @dataProvider workUnits */
    public function test_registration_routes_each_unit_to_its_own_ptsp($unit)
    {
        $localStaff = $this->staff($unit);
        $this->staff('Satker lain', ['is_online' => true, 'can_monitor' => true]);
        $this->get('/konsultasi')->assertOk();
        $nonce=session('public_booking_nonce');
        $unitId=\App\WorkUnit::where('name',$unit)->value('id');
        $this->post('/konsultasi', ['name'=>'Pendaftar satker uji','channel'=>'chat','topic'=>'Pertanyaan pengujian',
            'work_unit_id'=>$unitId,'consent'=>'1','request_key'=>$nonce])->assertRedirect();
        $this->assertGuest();
        $room = ChatRoom::firstOrFail();
        $this->assertSame($unit, $room->work_unit);
        $this->assertEquals($localStaff->id, $room->admin_id);
        $ticket=\App\GuestTicket::firstOrFail();
        $this->get('/tiket/'.$ticket->public_id)->assertOk()->assertSee($unit);
        $this->actingAs($localStaff)->get('/chat')->assertOk()->assertSee('Pendaftar satker uji');
    }

    public function workUnits()
    {
        return [
            ['Pengadilan Tinggi Agama Papua Barat'], ['Pengadilan Agama Sorong'],
            ['Pengadilan Agama Manokwari'], ['Pengadilan Agama Kaimana'], ['Pengadilan Agama Fak-Fak'],
        ];
    }

    public function test_email_registration_also_requires_a_unit_and_creates_a_room()
    {
        $staff = $this->staff();
        $data = ['name' => 'Pendaftar email', 'email' => 'citizen@example.test',
            'password' => 'Testing-User-2026', 'password_confirmation' => 'Testing-User-2026'];
        $this->postJson('/register', $data)->assertStatus(422)->assertJsonValidationErrors('work_unit');
        $this->post('/register', array_merge($data, ['work_unit' => $staff->work_unit]))
            ->assertRedirect(route('dashboard'));
        $this->assertEquals($staff->id, ChatRoom::firstOrFail()->admin_id);
    }

    public function test_absent_local_staff_never_falls_back_to_another_unit()
    {
        $monitor = $this->staff('Pengadilan Tinggi Agama Papua Barat', ['can_monitor' => true, 'is_online' => true]);
        $citizen = $this->citizen();
        $room = $this->room($citizen);
        $this->assertNull($room->admin_id);
        $this->assertSame($citizen->work_unit, $room->work_unit);
        $this->actingAs($citizen)->postJson('/chat/send', ['chat_room_id' => $room->id, 'message' => 'Menunggu petugas lokal'])
            ->assertOk();
        $localStaff = $this->staff();
        $this->actingAs($localStaff)->get('/chat')->assertOk()->assertSee($citizen->name);
        $this->getJson('/notifications/fetch')->assertOk()->assertJsonPath('unread_chats', 1);
        $this->postJson('/chat/send', ['chat_room_id' => $room->id, 'message' => 'PTSP Sorong siap membantu'])
            ->assertOk();
        $this->assertEquals($localStaff->id, $room->fresh()->admin_id);
        $this->assertNotEquals($monitor->id, $room->fresh()->admin_id);
    }

    public function test_offline_local_staff_is_preferred_to_online_central_staff()
    {
        $local = $this->staff();
        $this->staff('Pengadilan Tinggi Agama Papua Barat', ['can_monitor' => true, 'is_online' => true]);
        $this->assertEquals($local->id, $this->room($this->citizen())->admin_id);
    }

    public function test_team_can_reply_and_another_unit_cannot_read_fetch_or_send()
    {
        $assigned = $this->staff();
        $teamMember = $this->staff();
        $otherUnit = $this->staff('Pengadilan Agama Manokwari');
        $citizen = $this->citizen();
        $room = $this->room($citizen);
        $this->assertEquals($assigned->id, $room->admin_id);

        $this->actingAs($teamMember)->get('/chat/'.$room->id)->assertOk()->assertSee($citizen->name);
        $this->postJson('/chat/send', ['chat_room_id' => $room->id, 'message' => 'Jawaban tim PTSP'])->assertOk();
        $this->actingAs($citizen)->getJson('/chat/'.$room->id.'/fetch')->assertOk()
            ->assertJsonPath('messages.0.message', 'Jawaban tim PTSP');
        $this->actingAs($otherUnit)->get('/chat')->assertOk()->assertDontSee($citizen->name);
        $this->get('/chat/'.$room->id)->assertNotFound();
        $this->getJson('/chat/'.$room->id.'/fetch')->assertNotFound();
        $this->postJson('/chat/send', ['chat_room_id' => $room->id, 'message' => 'Tidak berhak'])->assertNotFound();
        $this->getJson('/notifications/fetch')->assertOk()->assertJsonPath('unread_chats', 0);
    }

    public function test_public_users_cannot_access_another_citizens_room_or_override_routing()
    {
        $local = $this->staff();
        $otherStaff = $this->staff('Pengadilan Agama Manokwari');
        $room = $this->room($this->citizen());
        $citizen = $this->citizen();
        $this->actingAs($citizen)->get('/chat/'.$room->id)->assertNotFound();
        $this->getJson('/chat/'.$room->id.'/fetch')->assertNotFound();
        $this->postJson('/chat/send', ['chat_room_id' => $room->id, 'message' => 'Tidak berhak'])->assertNotFound();
        $this->post('/chat/create-room', ['work_unit' => $otherStaff->work_unit, 'admin_id' => $otherStaff->id])
            ->assertRedirect();
        $own = ChatRoom::where('user_id', $citizen->id)->firstOrFail();
        $this->assertSame($local->work_unit, $own->work_unit);
        $this->assertEquals($local->id, $own->admin_id);
    }

    public function test_chat_voice_and_service_entry_reuse_open_conversations()
    {
        $this->staff();
        $this->actingAs($this->citizen())->get('/chat')->assertRedirect();
        $this->get('/chat')->assertRedirect();
        $this->get('/chat/voice-mode')->assertOk();
        $this->assertSame(1, ChatRoom::count());
        $service = Service::create(['name' => 'Layanan uji', 'is_active' => true]);
        $this->post('/chat/create-room', ['service_id' => $service->id])->assertRedirect();
        $this->post('/chat/create-room', ['service_id' => $service->id])->assertRedirect();
        $this->assertSame(2, ChatRoom::count());
        $this->assertSame(1, ChatRoom::where('service_id', $service->id)->count());
    }

    public function test_monitors_cannot_reply_across_units_or_clear_unit_unread_messages()
    {
        $local = $this->staff();
        $monitor = $this->staff('Pengadilan Tinggi Agama Papua Barat', ['can_monitor' => true]);
        $citizen = $this->citizen();
        $room = $this->room($citizen);
        $message = ChatMessage::create(['chat_room_id' => $room->id, 'sender_id' => $citizen->id, 'message' => 'Pesan masuk']);
        $this->actingAs($monitor)->get('/chat/'.$room->id)->assertOk()->assertDontSee('id="chatInput"', false);
        $this->getJson('/chat/'.$room->id.'/fetch')->assertOk();
        $this->assertFalse((bool) $message->fresh()->is_read);
        $this->postJson('/chat/send', ['chat_room_id' => $room->id, 'message' => 'Bukan satker saya'])->assertForbidden();
        $this->actingAs($local)->getJson('/notifications/fetch')->assertJsonPath('unread_chats', 1);
        $this->get('/chat/'.$room->id)->assertOk();
        $this->getJson('/notifications/fetch')->assertJsonPath('unread_chats', 0);
        $this->assertTrue((bool) $message->fresh()->is_read);
    }

    public function test_closed_rooms_cannot_receive_messages_and_voice_starts_a_new_room()
    {
        $this->staff();
        $citizen = $this->citizen();
        $room = $this->room($citizen);
        $room->update(['status' => 'closed']);
        $this->actingAs($citizen)->postJson('/chat/send', ['chat_room_id' => $room->id, 'message' => 'Tutup'])->assertForbidden();
        $this->get('/chat/voice-mode')->assertOk();
        $this->assertSame(2, ChatRoom::count());
        $this->assertSame(1, ChatRoom::where('status', 'open')->count());
    }

    public function test_missing_unit_does_not_create_unscoped_room()
    {
        $this->staff('Pengadilan Tinggi Agama Papua Barat', ['can_monitor' => true]);
        $this->actingAs($this->citizen(null))->get('/chat')->assertRedirect(route('guest.welcome'));
        $this->assertSame(0, ChatRoom::count());
    }

    public function test_existing_wrong_assignment_is_repaired_when_citizen_opens_chat()
    {
        $local = $this->staff();
        $central = $this->staff('Pengadilan Tinggi Agama Papua Barat', ['can_monitor' => true]);
        $citizen = $this->citizen();
        $room = ChatRoom::create(['user_id' => $citizen->id, 'admin_id' => $central->id,
            'work_unit' => $citizen->work_unit, 'status' => 'open']);
        $this->actingAs($citizen)->get('/chat')->assertRedirect(route('chat.show', $room->id));
        $this->assertEquals($local->id, $room->fresh()->admin_id);
    }

    public function test_existing_rooms_are_backfilled_without_losing_messages()
    {
        $local = $this->staff();
        $central = $this->staff('Pengadilan Tinggi Agama Papua Barat', ['can_monitor' => true]);
        $citizen = $this->citizen();
        Schema::drop('chat_messages');
        Schema::drop('chat_rooms');
        require_once database_path('migrations/2024_01_01_000002_create_chat_rooms_table.php');
        require_once database_path('migrations/2024_01_01_000003_create_chat_messages_table.php');
        (new \CreateChatRoomsTable)->up();
        (new \CreateChatMessagesTable)->up();
        $id = DB::table('chat_rooms')->insertGetId(['user_id' => $citizen->id, 'admin_id' => $central->id, 'status' => 'open']);
        ChatMessage::create(['chat_room_id' => $id, 'sender_id' => $citizen->id, 'message' => 'Riwayat tetap utuh']);
        require_once database_path('migrations/2026_10_27_000008_add_work_unit_to_chat_rooms_table.php');
        (new \AddWorkUnitToChatRoomsTable)->up();
        $room = ChatRoom::findOrFail($id);
        $this->assertSame($citizen->work_unit, $room->work_unit);
        $this->assertEquals($local->id, $room->admin_id);
        $this->assertSame('Riwayat tetap utuh', $room->messages()->first()->message);
    }
}
