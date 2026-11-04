<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPublicAccess extends Migration
{
    public function up()
    {
        $this->createTable('regions', function (Blueprint $t) {
            $t->string('code', 13)->primary();
            $t->string('parent_code', 13)->nullable()->index();
            $t->unsignedTinyInteger('level')->index();
            $t->string('name', 150);
            $t->string('source_version', 100);
            $t->index(['parent_code', 'name']);
        });
        $this->createTable('court_territories', function (Blueprint $t) {
            $t->id();
            $t->string('region_code', 13)->unique();
            $t->foreign('region_code')->references('code')->on('regions');
            $t->foreignId('work_unit_id')->constrained('work_units');
            $t->string('source_title');
            $t->string('source_url', 1000);
            $t->date('effective_date');
            $t->boolean('is_verified')->default(false);
            $t->foreignId('verified_by')->nullable()->constrained('users');
            $t->timestamps();
        });
        if (!Schema::hasColumn('work_units', 'website')) Schema::table('work_units', function (Blueprint $t) {
            $t->string('website', 500)->nullable();
            $t->text('public_requirements')->nullable();
        });
        if (!Schema::hasColumn('users', 'is_guest')) Schema::table('users', function (Blueprint $t) { $t->boolean('is_guest')->default(false)->index(); });
        $this->createTable('guest_tickets', function (Blueprint $t) {
            $t->id();
            $t->string('public_id', 32)->unique();
            $t->string('request_key', 64)->unique();
            $t->foreignId('user_id')->constrained('users');
            $t->foreignId('meeting_id')->nullable()->unique()->constrained('meetings');
            $t->foreignId('chat_room_id')->nullable()->unique()->constrained('chat_rooms');
            $t->string('phone', 20)->nullable();
            $t->string('region_code', 13)->nullable();
            $t->json('context')->nullable();
            $t->string('invite_hash', 64)->nullable()->unique();
            $t->text('invite_secret')->nullable();
            $t->timestamp('consented_at')->nullable();
            $t->dateTime('expires_at');
            $t->timestamps();
        });
        if (!Schema::hasColumn('meetings', 'called_at')) Schema::table('meetings', function (Blueprint $t) {
            $t->timestamp('called_at')->nullable();
            $t->foreignId('called_by')->nullable()->constrained('users');
        });
        $this->createTable('whatsapp_notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('guest_ticket_id')->unique()->constrained('guest_tickets');
            $t->string('status', 30)->default('queued')->index();
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->string('provider_id', 100)->nullable()->unique();
            $t->string('provider_state_id', 150)->nullable()->index();
            $t->string('provider_status', 50)->nullable();
            $t->string('last_error', 500)->nullable();
            $t->timestamp('attempted_at')->nullable();
            $t->timestamp('accepted_at')->nullable();
            $t->timestamp('delivered_at')->nullable();
            $t->timestamps();
        });
        $this->createTable('fee_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('work_unit_id')->constrained('work_units');
            $t->string('case_type', 80);
            $t->string('source_title');
            $t->string('source_url', 1000);
            $t->date('effective_date');
            $t->date('expires_on')->nullable();
            $t->json('components');
            $t->boolean('is_published')->default(false);
            $t->foreignId('verified_by')->nullable()->constrained('users');
            $t->timestamps();
            $t->index(['work_unit_id', 'case_type', 'is_published']);
        });
        $this->createTable('fee_radii', function (Blueprint $t) {
            $t->id();
            $t->foreignId('fee_schedule_id')->constrained('fee_schedules');
            $t->string('region_code', 13);
            $t->foreign('region_code')->references('code')->on('regions');
            $t->string('label', 100);
            $t->unsignedInteger('amount');
            $t->timestamps();
            $t->unique(['fee_schedule_id', 'region_code']);
        });
    }

    // MariaDB DDL is not transactional; an interrupted deployment can resume without dropping data.
    private function createTable($name, $callback)
    {
        if (!Schema::hasTable($name)) Schema::create($name, $callback);
    }

    public function down()
    {
        Schema::dropIfExists('fee_radii');
        Schema::dropIfExists('fee_schedules');
        Schema::dropIfExists('whatsapp_notifications');
        Schema::table('meetings', function (Blueprint $t) { $t->dropForeign(['called_by']); $t->dropColumn(['called_at', 'called_by']); });
        Schema::dropIfExists('guest_tickets');
        Schema::table('users', function (Blueprint $t) { $t->dropColumn('is_guest'); });
        Schema::table('work_units', function (Blueprint $t) { $t->dropColumn(['website', 'public_requirements']); });
        Schema::dropIfExists('court_territories');
        Schema::dropIfExists('regions');
    }
}
