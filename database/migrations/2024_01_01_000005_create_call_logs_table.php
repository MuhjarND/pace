<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallLogsTable extends Migration
{
    public function up()
    {
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('caller_id');
            $table->unsignedBigInteger('receiver_id');
            $table->integer('duration')->default(0);
            $table->enum('status', ['missed', 'answered', 'ongoing', 'ended'])->default('ongoing');
            $table->enum('type', ['audio', 'video'])->default('audio');
            $table->timestamps();

            $table->foreign('caller_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('receiver_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('call_logs');
    }
}
