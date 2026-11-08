<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConsultationJourneys extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('work_units', 'latitude')) {
            Schema::table('work_units', function (Blueprint $table) {
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->decimal('travel_km_per_liter', 10, 3)->nullable();
                $table->unsignedInteger('travel_fuel_price')->nullable();
            });
        }
        if (!Schema::hasTable('consultation_journeys')) {
            Schema::create('consultation_journeys', function (Blueprint $table) {
                $table->id();
                $table->foreignId('guest_ticket_id')->unique()->constrained('guest_tickets');
                $table->foreignId('work_unit_id')->constrained('work_units');
                $table->text('origin')->nullable();
                $table->decimal('destination_latitude', 10, 7)->nullable();
                $table->decimal('destination_longitude', 10, 7)->nullable();
                $table->string('status', 40)->default('missing_location')->index();
                $table->unsignedBigInteger('distance_meters')->nullable();
                $table->unsignedBigInteger('duration_seconds')->nullable();
                $table->decimal('km_per_liter', 10, 3)->nullable();
                $table->unsignedInteger('fuel_price')->nullable();
                $table->unsignedBigInteger('fuel_cost')->nullable();
                $table->string('source', 100)->nullable();
                $table->timestamp('calculated_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('consultation_journeys');
        Schema::table('work_units', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'travel_km_per_liter', 'travel_fuel_price']);
        });
    }
}
