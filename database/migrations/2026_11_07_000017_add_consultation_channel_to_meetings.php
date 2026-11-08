<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConsultationChannelToMeetings extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('meetings', 'channel')) {
            Schema::table('meetings', function (Blueprint $table) {
                $table->string('channel', 20)->default('meet');
            });
        }
    }

    public function down()
    {
        Schema::table('meetings', function (Blueprint $table) { $table->dropColumn('channel'); });
    }
}
