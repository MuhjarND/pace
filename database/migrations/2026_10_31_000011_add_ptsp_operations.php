<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPtspOperations extends Migration
{
    public function up()
    {
        Schema::create('work_units', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 40)->unique();
            $table->boolean('is_active')->default(true);
            $table->string('address', 500)->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('schedule_enabled')->default(false);
            $table->unsignedInteger('daily_quota')->nullable();
            $table->timestamps();
        });

        $names = collect(config('ptsp.work_units', []));
        foreach (['users', 'chat_rooms', 'meetings', 'consultation_settings', 'consultation_slots'] as $table) {
            $names = $names->merge(DB::table($table)->whereNotNull('work_unit')->distinct()->pluck('work_unit'));
        }
        foreach ($names->filter()->unique()->values() as $index => $name) {
            DB::table('work_units')->insert([
                'name' => $name, 'code' => 'PTSP-'.str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'is_active' => true, 'schedule_enabled' => false, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('auth_version')->default(1);
        });
        Schema::table('meetings', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable();
        });
        Schema::create('service_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_unit_id')->constrained('work_units');
            $table->unsignedTinyInteger('weekday');
            $table->boolean('is_open')->default(false);
            $table->string('opens_at', 5)->nullable();
            $table->string('closes_at', 5)->nullable();
            $table->string('break_start', 5)->nullable();
            $table->string('break_end', 5)->nullable();
            $table->timestamps();
            $table->unique(['work_unit_id', 'weekday']);
        });
        Schema::create('service_holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_unit_id')->constrained('work_units');
            $table->date('date');
            $table->string('name', 150);
            $table->timestamps();
            $table->unique(['work_unit_id', 'date']);
        });
        Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users');
            $table->string('action', 50);
            $table->string('target_type', 30);
            $table->unsignedBigInteger('target_id');
            $table->json('changes')->nullable();
            $table->timestamps();
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_activity_logs');
        Schema::dropIfExists('service_holidays');
        Schema::dropIfExists('service_hours');
        Schema::table('meetings', function (Blueprint $table) { $table->dropColumn('started_at'); });
        Schema::table('users', function (Blueprint $table) { $table->dropColumn(['is_active', 'auth_version']); });
        Schema::dropIfExists('work_units');
    }
}
