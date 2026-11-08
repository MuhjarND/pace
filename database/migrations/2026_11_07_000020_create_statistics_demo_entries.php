<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatisticsDemoEntries extends Migration
{
    public function up()
    {
        if (Schema::hasTable('statistics_demo_entries')) return;
        Schema::create('statistics_demo_entries', function (Blueprint $table) {
            $table->id();
            $table->string('sample_key', 50)->unique();
            $table->foreignId('work_unit_id')->constrained('work_units');
            $table->string('channel',20);
            $table->string('status',20);
            $table->string('purpose',100);
            $table->unsignedBigInteger('distance_meters');
            $table->unsignedBigInteger('duration_seconds');
            $table->unsignedBigInteger('fuel_cost');
            $table->index(['work_unit_id','channel','status']);
        });
    }
    public function down() { Schema::dropIfExists('statistics_demo_entries'); }
}
