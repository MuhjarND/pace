<?php

namespace Tests\Feature;

use App\ChatRoom;
use App\GuestTicket;
use App\Meeting;
use App\Services\PublicStatisticsService;
use App\User;
use App\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StatisticsDemoTest extends TestCase
{
    use RefreshDatabase;
    public function createApplication()
    {
        $app=parent::createApplication(); $app['config']->set('database.default','sqlite');
        $app['config']->set('database.connections.sqlite.database',':memory:'); $app['config']->set('hashing.bcrypt.rounds',4);
        return $app;
    }
    private function seedDemo() { $this->artisan('ptsp:seed-statistics-demo')->assertExitCode(0); }

    public function test_demo_numbers_are_consistent_and_never_create_operational_consultations()
    {
        $this->seedDemo();
        $stats=app(PublicStatisticsService::class)->snapshot(null,'demo');
        $this->assertSame(320,$stats['total']);
        $this->assertSame(['meet'=>84,'chat'=>172,'mace'=>64],$stats['channels']);
        $this->assertSame(['completed'=>272,'active'=>38,'cancelled'=>10],$stats['statuses']);
        $this->assertSame(5,$stats['served_units']);
        $this->assertEquals(11420,$stats['estimate']['distance_km']);
        $this->assertSame(17130,$stats['estimate']['minutes']);
        $this->assertSame(9516667,$stats['estimate']['fuel_cost']);
        $this->assertSame(320,array_sum($stats['purposes']));
        $this->assertSame(320,array_sum(array_column($stats['by_unit'],'total')));
        $this->assertSame(0,User::count()); $this->assertSame(0,Meeting::count());
        $this->assertSame(0,ChatRoom::count()); $this->assertSame(0,GuestTicket::count());
        $this->seedDemo(); $this->assertSame(320,DB::table('statistics_demo_entries')->count());
    }

    public function test_public_preview_can_switch_to_live_filter_courts_and_change_chart()
    {
        $user=factory(User::class)->create();
        $unit=WorkUnit::where('name','Pengadilan Agama Sorong')->firstOrFail();
        Meeting::create(['user_id'=>$user->id,'work_unit'=>$unit->name,'topic'=>'Privat','start_time'=>now(),'duration'=>30,'status'=>'scheduled']);
        $this->seedDemo();
        $this->get('/')->assertOk()->assertSee('class="stats-demo-badge"',false)
            ->assertSee('Data contoh untuk pratinjau tampilan.')->assertViewHas('statistics',function($stats){return $stats['total']===320 && $stats['data_mode']==='demo';});
        $this->get('/?stats_mode=live')->assertOk()->assertDontSee('class="stats-demo-badge"',false)
            ->assertViewHas('statistics',function($stats){return $stats['total']===1 && $stats['data_mode']==='live';});
        $this->get('/?stats_mode=demo&stats_unit='.$unit->id.'&stats_chart=statuses')->assertOk()
            ->assertSee('aria-label="Status per satker"',false)->assertViewHas('statistics',function($stats){
                return $stats['total']===120 && $stats['channels']===['meet'=>30,'chat'=>66,'mace'=>24]
                    && $stats['statuses']===['completed'=>102,'active'=>14,'cancelled'=>4];
            });
        $this->getJson('/?stats_mode=unknown')->assertStatus(422)->assertJsonValidationErrors('stats_mode');
        $this->getJson('/?stats_chart=unknown')->assertStatus(422)->assertJsonValidationErrors('stats_chart');
    }

    public function test_clearing_demo_entries_returns_the_default_to_live_and_preserves_real_records()
    {
        $user=factory(User::class)->create();
        $this->seedDemo(); $this->assertSame('demo',app(PublicStatisticsService::class)->defaultMode());
        $this->artisan('ptsp:seed-statistics-demo',['--clear'=>true])->assertExitCode(0);
        $this->assertSame(0,DB::table('statistics_demo_entries')->count());
        $this->assertSame('live',app(PublicStatisticsService::class)->defaultMode());
        $this->assertTrue(User::whereKey($user->id)->exists());
    }
}
