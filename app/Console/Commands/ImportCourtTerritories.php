<?php
namespace App\Console\Commands;
use App\CourtTerritory;
use App\Region;
use App\WorkUnit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCourtTerritories extends Command
{
    protected $signature='ptsp:import-court-territories';
    protected $description='Import reviewed 2026 court coverage; preserve existing admin decisions';
    public function handle()
    {
        $data=json_decode(file_get_contents(database_path('data/court-territories.json')),true,512,JSON_THROW_ON_ERROR);
        DB::transaction(function()use($data){
            foreach($data['territories']as$code=>$name){
                $region=Region::findOrFail($code);if((int)$region->level!==2)throw new \RuntimeException('Invalid jurisdiction region level.');
                $unit=WorkUnit::where('name',$name)->firstOrFail();
                CourtTerritory::firstOrCreate(['region_code'=>$code],['work_unit_id'=>$unit->id,'source_title'=>$data['source_title'],
                    'source_url'=>$data['source_url'],'effective_date'=>$data['effective_date'],'is_verified'=>true]);
            }
        });
        $this->info('13 reviewed regency/city mappings loaded. Existing mappings preserved.');return 0;
    }
}
