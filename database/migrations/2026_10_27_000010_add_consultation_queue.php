<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AddConsultationQueue extends Migration
{
    public function up()
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->date('queue_date')->nullable();
            $table->unsignedInteger('queue_number')->nullable();
            $table->unique(['work_unit', 'queue_date', 'queue_number'], 'meetings_unit_daily_queue_unique');
        });
        DB::table('meetings')->orderBy('start_time')->orderBy('id')->get()->each(function ($meeting) {
            $date = Carbon::parse($meeting->start_time, 'UTC')->timezone('Asia/Jayapura')->format('Y-m-d');
            $number = (int) DB::table('meetings')->where('work_unit', $meeting->work_unit)->where('queue_date', $date)->max('queue_number') + 1;
            DB::table('meetings')->where('id', $meeting->id)->update(['queue_date' => $date, 'queue_number' => $number]);
        });
    }

    public function down()
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropUnique('meetings_unit_daily_queue_unique');
            $table->dropColumn(['queue_date', 'queue_number']);
        });
    }
}
