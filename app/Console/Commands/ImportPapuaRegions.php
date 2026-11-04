<?php
namespace App\Console\Commands;
use App\Region;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportPapuaRegions extends Command
{
    protected $signature = 'ptsp:import-regions {--check : Validate without writing}';
    protected $description = 'Import the pinned Papua Barat and Papua Barat Daya administrative dataset without executing source SQL';
    public function handle()
    {
        $data = json_decode(file_get_contents(database_path('data/papua-regions.json')), true, 512, JSON_THROW_ON_ERROR);
        $seen = []; $counts = [1=>0, 2=>0, 3=>0, 4=>0];
        foreach ($data['regions'] as $r) {
            if (!preg_match('/\A(?:92|96)(?:\.[0-9]{2}(?:\.[0-9]{2}(?:\.[0-9]{4})?)?)?\z/', $r['code']) || isset($seen[$r['code']]) || trim($r['name']) === '') throw new \RuntimeException('Invalid or duplicate region code.');
            $parts = explode('.', $r['code']); $level = count($parts); array_pop($parts);
            $parent = $parts ? implode('.', $parts) : null;
            if ($parent && !isset($seen[$parent])) throw new \RuntimeException('Missing parent: '.$parent);
            $seen[$r['code']] = ['code'=>$r['code'], 'name'=>$r['name'], 'level'=>$level, 'parent_code'=>$parent, 'source_version'=>$data['version']];
            $counts[$level]++;
        }
        if ($counts !== $data['counts']) throw new \RuntimeException('Dataset count mismatch.');
        $this->line(json_encode($counts));
        if (!$this->option('check')) {
            DB::transaction(function () use ($seen) { foreach ($seen as $row) Region::updateOrCreate(['code'=>$row['code']], $row); });
            $this->info('Administrative regions imported. Court jurisdictions and tariffs are separate datasets.');
        }
        return 0;
    }
}
