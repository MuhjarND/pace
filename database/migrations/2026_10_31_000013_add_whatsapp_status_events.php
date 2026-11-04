<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddWhatsappStatusEvents extends Migration
{
    public function up(){Schema::create('whatsapp_status_events',function(Blueprint $t){
        $t->id();$t->string('fingerprint',64)->unique();$t->string('provider_id',100)->nullable()->index();
        $t->string('state_id',150)->nullable()->index();$t->string('status',50)->nullable();$t->string('state',50)->nullable();
        $t->timestamp('processed_at')->nullable();$t->timestamps();
    });}
    public function down(){Schema::dropIfExists('whatsapp_status_events');}
}
