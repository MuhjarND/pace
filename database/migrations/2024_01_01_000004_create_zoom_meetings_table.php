<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZoomMeetingsTable extends Migration
{
    public function up()
    {
        Schema::create('zoom_meetings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('topic');
            $table->dateTime('start_time');
            $table->integer('duration')->default(30);
            $table->string('meeting_id')->nullable();
            $table->string('join_url')->nullable();
            $table->string('password')->nullable();
            $table->enum('status', ['scheduled', 'started', 'ended', 'cancelled'])->default('scheduled');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('zoom_meetings');
    }
}
