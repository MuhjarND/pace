<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddComplaints extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('users', 'can_handle_complaints')) Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_handle_complaints')->default(false);
        });
        if (!Schema::hasTable('complaints')) Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->char('request_hash', 64)->unique();
            $table->char('access_hash', 64);
            $table->foreignId('work_unit_id')->constrained('work_units');
            $table->string('type', 30);
            $table->string('status', 30)->default('received');
            $table->date('occurred_on');
            $table->text('subject');
            $table->text('description');
            $table->text('location')->nullable();
            $table->text('reported_person')->nullable();
            $table->text('reporter_name')->nullable();
            $table->text('reporter_contact')->nullable();
            $table->boolean('is_anonymous')->default(true);
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->unsignedInteger('lock_version')->default(1);
            $table->dateTime('consented_at');
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['work_unit_id', 'type']);
        });
        if (!Schema::hasTable('complaint_attachments')) Schema::create('complaint_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('complaints');
            $table->string('path', 255);
            $table->string('name', 40);
            $table->string('mime', 60);
            $table->unsignedInteger('bytes');
            $table->char('sha256', 64);
            $table->timestamps();
        });
        if (!Schema::hasTable('complaint_events')) Schema::create('complaint_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('complaints');
            $table->foreignId('actor_id')->nullable()->constrained('users');
            $table->string('status', 30);
            $table->text('public_message');
            $table->text('internal_note')->nullable();
            $table->timestamps();
            $table->index(['complaint_id', 'id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('complaint_events');
        Schema::dropIfExists('complaint_attachments');
        Schema::dropIfExists('complaints');
        Schema::table('users', function (Blueprint $table) { $table->dropColumn('can_handle_complaints'); });
    }
}
