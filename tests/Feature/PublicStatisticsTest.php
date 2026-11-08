<?php

namespace Tests\Feature;

use App\ChatRoom;
use App\ConsultationJourney;
use App\GuestTicket;
use App\Meeting;
use App\Service;
use App\Services\PublicStatisticsService;
use App\Services\TravelEstimateService;
use App\User;
use App\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('hashing.bcrypt.rounds', 4);
        return $app;
    }

    private function fixtures()
    {
        $user = factory(User::class)->create(['name'=>'Identitas sangat privat', 'email'=>'rahasia@example.test']);
        $unit = WorkUnit::where('name','Pengadilan Agama Sorong')->firstOrFail();
        $other = WorkUnit::where('name','Pengadilan Agama Manokwari')->firstOrFail();
        $service = Service::create(['name'=>'Layanan uji','category'=>'perkara','description'=>'Uji','icon'=>'fa-gavel','is_active'=>true]);
        ChatRoom::create(['user_id'=>$user->id,'work_unit'=>$unit->name,'service_id'=>$service->id,'status'=>'closed']);
        ChatRoom::create(['user_id'=>$user->id,'work_unit'=>$other->name,'status'=>'open']);
        foreach ([['meet','ended',$unit->name], ['mace','scheduled',$unit->name], ['mace','cancelled',$other->name]] as $data) {
            Meeting::create(['user_id'=>$user->id,'work_unit'=>$data[2],'channel'=>$data[0],'status'=>$data[1],
                'topic'=>'Topik rahasia pemohon','start_time'=>now(),'verification_status'=>'immediate','duration'=>30]);
        }
        GuestTicket::create(['user_id'=>$user->id,'meeting_id'=>Meeting::first()->id,'public_id'=>'tiket-pribadi-uji',
            'request_key'=>str_repeat('x',64),'expires_at'=>now()->addDay()]);
        return [$unit, $other];
    }

    public function test_public_pages_render_their_page_specific_stylesheets_in_the_document_head()
    {
        foreach (['/'=>'css/public-statistics.css', '/cari-pengadilan'=>'css/public-statistics.css', '/konsultasi'=>'css/consultation-location.css'] as $url=>$stylesheet) {
            $response = $this->get($url)->assertOk();
            $html = $response->getContent();
            $head = strstr($html, '</head>', true);
            $this->assertNotFalse($head);
            $this->assertStringContainsString(asset($stylesheet), $head, 'Missing page stylesheet on '.$url);
            $response->assertDontSee("@yield('styles')", false);
        }
    }

    public function test_counts_each_consultation_once_by_channel_status_court_and_purpose_without_private_fields()
    {
        $this->fixtures();
        $stats=app(PublicStatisticsService::class)->snapshot();
        $this->assertSame(5,$stats['total']);
        $this->assertSame(['meet'=>1,'chat'=>2,'mace'=>2],$stats['channels']);
        $this->assertSame(['completed'=>2,'active'=>2,'cancelled'=>1],$stats['statuses']);
        $this->assertSame(2,$stats['served_units']); $this->assertSame(3,$stats['online']);
        $this->assertSame(3,$stats['purposes']['Konsultasi PTSP']); $this->assertSame(1,$stats['purposes']['Perkara']);
        $this->assertSame(5,array_sum(array_column($stats['by_unit'],'total')));
        $this->get('/')->assertOk()->assertSee('Statistik layanan')->assertSee('Mace (offline)')
            ->assertDontSee('Identitas sangat privat')->assertDontSee('rahasia@example.test')
            ->assertDontSee('Topik rahasia pemohon')->assertDontSee('tiket-pribadi-uji');
    }

    public function test_selected_court_uses_recorded_routes_and_ignores_average_distance_query_parameters()
    {
        [$unit]=$this->fixtures();
        ConsultationJourney::create(['guest_ticket_id'=>GuestTicket::first()->id,'work_unit_id'=>$unit->id,
            'status'=>'routed','distance_meters'=>42000,'duration_seconds'=>4800,'fuel_cost'=>45000]);
        $otherMeeting=Meeting::where('work_unit','Pengadilan Agama Manokwari')->firstOrFail();
        $otherTicket=GuestTicket::create(['user_id'=>$otherMeeting->user_id,'meeting_id'=>$otherMeeting->id,'public_id'=>'other-route-ticket',
            'request_key'=>str_repeat('z',64),'expires_at'=>now()->addDay()]);
        ConsultationJourney::create(['guest_ticket_id'=>$otherTicket->id,'work_unit_id'=>WorkUnit::where('name',$otherMeeting->work_unit)->value('id'),
            'status'=>'routed','distance_meters'=>18000,'duration_seconds'=>1200,'fuel_cost'=>null]);
        $totals=app(TravelEstimateService::class)->aggregate();
        $this->assertSame(60.0,$totals['distance_km']); $this->assertSame(100,$totals['minutes']);
        $this->assertSame(2,$totals['route_count']); $this->assertSame(1,$totals['fuel_count']);
        $stats=app(PublicStatisticsService::class)->snapshot($unit->id);
        $this->assertSame(3,$stats['total']); $this->assertSame(['meet'=>1,'chat'=>1,'mace'=>1],$stats['channels']);
        $this->assertSame(1,count($stats['by_unit']));
        $this->get('/?stats_unit='.$unit->id.'&trip_km=9999&trip_mode=one_way&trip_speed=1&trip_efficiency=1&trip_fuel_price=1')
            ->assertOk()->assertViewHas('statistics',function($value){
                return $value['total']===3 && $value['estimate']['distance_km']===42.0
                    && $value['estimate']['minutes']===80 && $value['estimate']['fuel_cost']===45000
                    && $value['estimate']['route_count']===1;
            });
    }

    public function test_travel_totals_do_not_invent_routes_for_missing_locations_and_keep_zero_routes_valid()
    {
        $service=app(TravelEstimateService::class);
        $empty=$service->aggregate();
        $this->assertNull($empty['distance_km']); $this->assertNull($empty['minutes']); $this->assertNull($empty['fuel_cost']);
        $this->get('/')->assertOk()->assertSee('Belum ada konsultasi yang tercatat')->assertSee('Data estimasi belum tersedia.')
            ->assertDontSee('NaN')->assertDontSee('INF');
        [$unit]=$this->fixtures();
        ConsultationJourney::create(['guest_ticket_id'=>GuestTicket::first()->id,'work_unit_id'=>$unit->id,
            'status'=>'routed','distance_meters'=>0,'duration_seconds'=>0,'fuel_cost'=>0]);
        $zero=$service->aggregate();
        $this->assertSame(0.0,$zero['distance_km']); $this->assertSame(0,$zero['minutes']); $this->assertSame(0,$zero['fuel_cost']);
        $this->assertSame(1,$zero['route_count']);
    }

    public function test_statistics_reject_unknown_courts()
    {
        $this->getJson('/?stats_unit=999999')->assertStatus(422)->assertJsonValidationErrors('stats_unit');
    }
}
