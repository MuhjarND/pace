<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class RenameZoomMeetingsTableToMeetings extends Migration
{
    public function up()
    {
        Schema::rename('zoom_meetings', 'meetings');
    }

    public function down()
    {
        Schema::rename('meetings', 'zoom_meetings');
    }
}
