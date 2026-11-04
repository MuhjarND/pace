<?php

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffSeeder extends Seeder
{
    public function run()
    {
        if (!app()->environment('local', 'testing')) {
            throw new RuntimeException('Akun dummy staf hanya tersedia untuk lingkungan local atau testing.');
        }

        $staff = [
            ['email' => 'pta.papuabarat@ptsp.go.id', 'name' => 'Admin PTSP PTA Papua Barat', 'role' => 'admin', 'work_unit' => 'Pengadilan Tinggi Agama Papua Barat', 'can_monitor' => true],
            ['email' => 'operator.pta.papuabarat@ptsp.go.id', 'name' => 'Operator PTSP PTA Papua Barat', 'role' => 'operator', 'work_unit' => 'Pengadilan Tinggi Agama Papua Barat', 'can_monitor' => true],
            ['email' => 'pa.sorong@ptsp.go.id', 'name' => 'Petugas PTSP PA Sorong', 'role' => 'operator', 'work_unit' => 'Pengadilan Agama Sorong', 'can_monitor' => false],
            ['email' => 'pa.manokwari@ptsp.go.id', 'name' => 'Petugas PTSP PA Manokwari', 'role' => 'operator', 'work_unit' => 'Pengadilan Agama Manokwari', 'can_monitor' => false],
            ['email' => 'pa.kaimana@ptsp.go.id', 'name' => 'Petugas PTSP PA Kaimana', 'role' => 'operator', 'work_unit' => 'Pengadilan Agama Kaimana', 'can_monitor' => false],
            ['email' => 'pa.fakfak@ptsp.go.id', 'name' => 'Petugas PTSP PA Fak-Fak', 'role' => 'operator', 'work_unit' => 'Pengadilan Agama Fak-Fak', 'can_monitor' => false],
        ];

        $path = storage_path('app/private/dummy-staff-credentials.json');
        $credentials = File::exists($path)
            ? json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR)
            : [];

        if (!is_array($credentials)) {
            throw new RuntimeException('Berkas kredensial dummy tidak valid.');
        }

        $created = 0;

        DB::transaction(function () use ($staff, $path, &$credentials, &$created) {
            foreach ($staff as $attributes) {
                $user = User::firstOrNew(['email' => $attributes['email']]);

                if ($user->exists) {
                    continue;
                }

                $password = Str::random(16).'!7aA';
                $user->fill(array_merge($attributes, [
                    'password' => Hash::make($password),
                    'phone' => null,
                    'is_online' => false,
                ]))->save();

                $credentials[$user->email] = array_merge($attributes, [
                    'password' => $password,
                    'created_at' => now()->toIso8601String(),
                ]);
                $created++;
            }

            if ($created > 0) {
                $directory = dirname($path);
                if (!File::isDirectory($directory) && !File::makeDirectory($directory, 0700, true)) {
                    throw new RuntimeException('Direktori kredensial dummy tidak dapat dibuat.');
                }

                if (File::put($directory.'/.htaccess', "Require all denied\n", true) === false) {
                    throw new RuntimeException('Direktori kredensial dummy tidak dapat dilindungi.');
                }

                $written = File::put($path, json_encode($credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL, true);
                if ($written === false) {
                    throw new RuntimeException('Kredensial dummy tidak dapat disimpan.');
                }
            }
        });

        if ($this->command) {
            $this->command->info($created.' akun staf dummy dibuat; akun yang sudah ada dipertahankan.');
            if ($created > 0) {
                $this->command->line('Kredensial: '.$path);
            }
        }
    }
}
