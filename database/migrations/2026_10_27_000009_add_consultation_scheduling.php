<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddConsultationScheduling extends Migration
{
    public function up()
    {
        Schema::create('consultation_settings', function (Blueprint $table) {
            $table->id();
            $table->string('work_unit')->unique();
            $table->string('join_url');
            $table->timestamps();
        });
        Schema::create('consultation_slots', function (Blueprint $table) {
            $table->id();
            $table->string('work_unit');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->index(['work_unit', 'starts_at']);
        });
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('work_unit')->nullable()->index();
            $table->foreignId('slot_id')->nullable()->constrained('consultation_slots')->onDelete('set null');
            $table->string('verification_status')->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('verified_at')->nullable();
            $table->text('verification_note')->nullable();
            $table->dateTime('completed_at')->nullable();
        });
        DB::table('meetings')->orderBy('id')->chunkById(100, function ($meetings) {
            foreach ($meetings as $meeting) {
                DB::table('meetings')->where('id', $meeting->id)->update([
                    'work_unit' => DB::table('users')->where('id', $meeting->user_id)->value('work_unit'),
                    'verification_status' => 'verified',
                ]);
            }
        });
    }

    public function down()
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropForeign(['slot_id']);
            $table->dropForeign(['verified_by']);
            $table->dropIndex(['work_unit']);
            $table->dropColumn(['work_unit', 'slot_id', 'verification_status', 'verified_by', 'verified_at', 'verification_note', 'completed_at']);
        });
        Schema::dropIfExists('consultation_slots');
        Schema::dropIfExists('consultation_settings');
    }
}
