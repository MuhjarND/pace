<?php

namespace Tests\Feature;

use App\ConsultationJourney;
use App\ConsultationSetting;
use App\GuestTicket;
use App\Region;
use App\Services\TravelEstimateService;
use App\User;
use App\WorkUnit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConsultationJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app=parent::createApplication(); $app['config']->set('database.default','sqlite');
        $app['config']->set('database.connections.sqlite.database',':memory:'); $app['config']->set('hashing.bcrypt.rounds',4);
        return $app;
    }
    protected function setUp(): void
    {
        parent::setUp(); Carbon::setTestNow(Carbon::parse('2026-11-07 00:00:00','UTC'));
        config(['services.travel.routing_url'=>'https://routes.example.test','services.travel.routing_profile'=>'driving']);
        $this->routeResponse();
    }
    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }
    private function unit()
    {
        $unit=WorkUnit::where('name','Pengadilan Agama Sorong')->firstOrFail();
        $unit->update(['latitude'=>-0.8,'longitude'=>131.3,'travel_km_per_liter'=>12,'travel_fuel_price'=>10000]);
        return $unit;
    }
    private function routeResponse($data = null)
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        $data=$data ?? ['code'=>'Ok','routes'=>[['distance'=>23000,'duration'=>2400]]];
        Http::fake(['*'=>new \GuzzleHttp\Promise\FulfilledPromise(new \GuzzleHttp\Psr7\Response(200,
            ['Content-Type'=>'application/json'],json_encode($data)))]);
    }
    private function payload($unit, $extra = [])
    {
        $this->origin();
        return array_merge(['request_key'=>session('public_booking_nonce'),'work_unit_id'=>$unit->id,
            'channel'=>'chat','name'=>'Pemohon uji','topic'=>'Pertanyaan layanan','consent'=>'1',
            'origin_region_code'=>'96.71.01.1001'],$extra);
    }
    private function origin()
    {
        foreach ([['96',null,1,'Papua Barat Daya'],['96.71','96',2,'Sorong'],['96.71.01','96.71',3,'Distrik uji'],['96.71.01.1001','96.71.01',4,'Kampung uji']] as $row) {
            Region::firstOrCreate(['code'=>$row[0]],['parent_code'=>$row[1],'level'=>$row[2],'name'=>$row[3],'source_version'=>'test',
                'latitude'=>$row[2]===4 ? -0.9 : null,'longitude'=>$row[2]===4 ? 131.2 : null]);
        }
        return Region::findOrFail('96.71.01.1001');
    }
    /** @dataProvider channels */
    public function test_booking_records_a_private_round_trip_route_for_each_channel($channel)
    {
        $unit=$this->unit(); ConsultationSetting::create(['work_unit'=>$unit->name,'join_url'=>'https://meet.google.com/abc-defg-hij']);
        $this->get('/konsultasi')->assertOk();
        $this->post('/konsultasi',$this->payload($unit,['channel'=>$channel,'phone'=>'081234567890',
            'consultation_date'=>'2026-11-07','consultation_time'=>'now']))->assertRedirect();
        $journey=ConsultationJourney::firstOrFail();
        $this->assertSame('routed',$journey->status); $this->assertSame(23000,$journey->distance_meters);
        $this->assertSame(2400,$journey->duration_seconds); $this->assertSame(19167,$journey->fuel_cost);
        $this->assertEquals(['latitude'=>-0.9,'longitude'=>131.2],$journey->origin);
        $this->assertStringNotContainsString('131.2',DB::table('consultation_journeys')->value('origin'));
        $this->assertArrayNotHasKey('origin',$journey->toArray());
        Http::assertSentCount(1);
        Http::assertSent(function($request){ return strpos($request->url(),
            '/131.2000000,-0.9000000;131.3000000,-0.8000000;131.2000000,-0.9000000')!==false; });
        $this->get('/')->assertOk()->assertDontSee('131.2000000')->assertDontSee('-0.9000000');
    }
    public function channels() { return [['chat'],['meet'],['mace']]; }

    public function test_retry_does_not_duplicate_the_route_or_change_its_historical_cost()
    {
        $unit=$this->unit(); $this->get('/konsultasi'); $payload=$this->payload($unit);
        $this->post('/konsultasi',$payload)->assertRedirect();
        $unit->update(['travel_fuel_price'=>20000,'latitude'=>-1]);
        $this->post('/konsultasi',$payload)->assertRedirect();
        $this->assertSame(1,ConsultationJourney::count()); Http::assertSentCount(1);
        $this->assertSame(19167,ConsultationJourney::first()->fuel_cost);
        $this->assertSame(23.0,app(TravelEstimateService::class)->aggregate()['distance_km']);
    }

    public function test_domicile_is_optional_and_must_be_a_registered_village()
    {
        $unit=$this->unit(); $this->get('/konsultasi');
        foreach ([['origin_region_code'=>'999'],['origin_region_code'=>'96'],['origin_region_code'=>['invalid']]] as $extra) {
            $this->postJson('/konsultasi',$this->payload($unit,$extra))->assertStatus(422);
        }
        $this->post('/konsultasi',$this->payload($unit,['origin_region_code'=>null]))->assertRedirect();
        $this->assertSame('missing_location',ConsultationJourney::first()->status); Http::assertNothingSent();
        $this->assertNull(app(TravelEstimateService::class)->aggregate()['distance_km']);
    }

    public function test_missing_court_coordinates_and_unavailable_roads_do_not_block_consultations()
    {
        $unit=$this->unit(); $unit->update(['latitude'=>null,'longitude'=>null]);
        $this->get('/konsultasi'); $this->post('/konsultasi',$this->payload($unit))->assertRedirect();
        $this->assertSame('missing_destination',ConsultationJourney::first()->status); Http::assertNothingSent();
        $unit=$this->unit(); $this->routeResponse(['code'=>'NoRoute']);
        $this->get('/konsultasi?new=1'); $this->post('/konsultasi',$this->payload($unit))->assertRedirect();
        $this->assertSame(2,GuestTicket::count());
        $this->assertSame('route_unavailable',ConsultationJourney::latest('id')->first()->status);
        $this->assertNull(app(TravelEstimateService::class)->aggregate()['distance_km']);
        $this->routeResponse(); $this->artisan('ptsp:refresh-travel',['--limit'=>10])->assertExitCode(0);
        $this->assertSame(2,ConsultationJourney::where('status','routed')->count());
    }

    public function test_fuel_is_unknown_without_rates_and_partial_coverage_is_not_reported_as_complete()
    {
        $unit=$this->unit(); $unit->update(['travel_km_per_liter'=>null,'travel_fuel_price'=>null]);
        $this->get('/konsultasi'); $this->post('/konsultasi',$this->payload($unit))->assertRedirect();
        $stats=app(TravelEstimateService::class)->aggregate();
        $this->assertSame(23.0,$stats['distance_km']); $this->assertSame(40,$stats['minutes']);
        $this->assertNull($stats['fuel_cost']); $this->assertSame(0,$stats['fuel_count']);
        $unit=$this->unit(); $this->artisan('ptsp:refresh-travel',['--limit'=>10])->assertExitCode(0);
        $this->assertSame(19167,app(TravelEstimateService::class)->aggregate()['fuel_cost']);
        Http::assertSentCount(1);
    }

    public function test_only_authorized_admin_can_set_court_location_and_fuel_references()
    {
        $unit=$this->unit();
        $user=factory(User::class)->create(['role'=>'operator','work_unit'=>$unit->name]);
        $payload=['name'=>$unit->name,'code'=>$unit->code,'is_active'=>1,'latitude'=>-1,'longitude'=>131,
            'travel_km_per_liter'=>10,'travel_fuel_price'=>12000];
        $this->actingAs($user)->put('/admin/work-units/'.$unit->id,$payload)->assertForbidden();
        $admin=factory(User::class)->create(['role'=>'admin','can_monitor'=>true,'work_unit'=>config('ptsp.supervising_work_unit')]);
        $this->actingAs($admin)->put('/admin/work-units/'.$unit->id,$payload)->assertRedirect();
        $this->assertEquals(-1,$unit->fresh()->latitude);
        $payload['longitude']=181; $this->putJson('/admin/work-units/'.$unit->id,$payload)->assertStatus(422)->assertJsonValidationErrors('longitude');
    }
    public function test_missing_domicile_reference_is_preserved_and_can_be_resolved_after_admin_updates_it()
    {
        $unit=$this->unit(); $region=$this->origin(); $region->update(['latitude'=>null,'longitude'=>null]);
        $this->get('/konsultasi'); $this->post('/konsultasi',$this->payload($unit))->assertRedirect();
        $this->assertSame('missing_origin',ConsultationJourney::first()->status); Http::assertNothingSent();
        $admin=factory(User::class)->create(['role'=>'admin','can_monitor'=>true,'work_unit'=>config('ptsp.supervising_work_unit')]);
        $reference=['region_code'=>$region->code,'latitude'=>-0.9,'longitude'=>131.2,'source_url'=>'https://example.test/reference'];
        $this->actingAs($admin)->post('/operations/public-data/travel-origins',$reference)->assertRedirect();
        $this->get('/operations/public-data')->assertOk()->assertSee('Titik acuan domisili')->assertSee('https://example.test/reference');
        $this->artisan('ptsp:refresh-travel',['--limit'=>10])->assertExitCode(0);
        $this->assertSame('routed',ConsultationJourney::first()->fresh()->status);
        $operator=factory(User::class)->create(['role'=>'operator','work_unit'=>$unit->name]);
        $this->actingAs($operator)->post('/operations/public-data/travel-origins',$reference)->assertForbidden();
    }
    public function test_browser_supplied_coordinates_and_distances_cannot_override_the_domicile_reference()
    {
        $unit=$this->unit(); $this->get('/konsultasi');
        $this->post('/konsultasi',$this->payload($unit,['origin_latitude'=>55,'origin_longitude'=>12,'distance_meters'=>1,'fuel_cost'=>1]))->assertRedirect();
        $this->assertEquals(['latitude'=>-0.9,'longitude'=>131.2],ConsultationJourney::first()->origin);
        $this->assertSame(23000,ConsultationJourney::first()->distance_meters);
    }
}
