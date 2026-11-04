<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\User;
use App\Service;
use App\ChatRoom;
use App\ChatMessage;
use App\Meeting;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(StaffSeeder::class);
        $admin = User::where('email', 'pta.papuabarat@ptsp.go.id')->firstOrFail();

        // Create Users
        $user1 = User::updateOrCreate(
            ['email' => 'budi@example.com'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'role' => 'user',
                'work_unit' => 'Pengadilan Agama Sorong',
                'phone' => '08123456789',
                'is_online' => true,
                'can_monitor' => false,
            ]
        );

        $user2 = User::updateOrCreate(
            ['email' => 'siti@example.com'],
            [
                'name' => 'Siti Rahayu',
                'password' => Hash::make('password'),
                'role' => 'user',
                'work_unit' => 'Pengadilan Agama Manokwari',
                'phone' => '08198765432',
                'is_online' => false,
                'can_monitor' => false,
            ]
        );

        $this->call(ServiceSeeder::class);

        // Create sample chat room
        $chatRoom = ChatRoom::create([
            'user_id' => $user1->id,
            'admin_id' => User::where('role', 'operator')->where('work_unit', $user1->work_unit)->firstOrFail()->id,
            'work_unit' => $user1->work_unit,
            'service_id' => Service::where('name', 'Pendaftaran Perkara Cerai Gugat')->firstOrFail()->id,
            'status' => 'open',
        ]);

        ChatMessage::create([
            'chat_room_id' => $chatRoom->id,
            'sender_id' => $user1->id,
            'message' => 'Selamat pagi, saya ingin berkonsultasi tentang pendaftaran perkara cerai gugat.',
            'type' => 'text',
        ]);

        ChatMessage::create([
            'chat_room_id' => $chatRoom->id,
            'sender_id' => $chatRoom->admin_id,
            'message' => 'Selamat pagi! Silakan sampaikan pertanyaan Anda. Petugas PTSP akan membantu menjelaskan informasi pendaftaran perkara.',
            'type' => 'text',
        ]);

        // Example schedule only; a real Google Meet link must be added by its host.
        Meeting::create([
            'user_id' => $user1->id,
            'admin_id' => $admin->id,
            'topic' => 'Konsultasi Persyaratan Perkara - Budi Santoso',
            'start_time' => now()->addDays(1)->setHour(10)->setMinute(0),
            'duration' => 30,
            'status' => 'scheduled',
        ]);
    }
}
