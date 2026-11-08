<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTravelOriginRegions extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('regions','latitude')) Schema::table('regions',function(Blueprint $table){
            $table->decimal('latitude',10,7)->nullable();
            $table->decimal('longitude',10,7)->nullable();
            $table->string('travel_source_url',1000)->nullable();
        });
        if (!Schema::hasColumn('consultation_journeys','origin_region_code')) Schema::table('consultation_journeys',function(Blueprint $table){
            $table->string('origin_region_code',13)->nullable()->index();
            $table->foreign('origin_region_code')->references('code')->on('regions');
        });
    }
    public function down()
    {
        Schema::table('consultation_journeys',function(Blueprint $table){$table->dropForeign(['origin_region_code']);$table->dropColumn('origin_region_code');});
        Schema::table('regions',function(Blueprint $table){$table->dropColumn(['latitude','longitude','travel_source_url']);});
    }
}
