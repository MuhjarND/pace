<?php

namespace App\Console\Commands;

use App\WorkUnit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedStatisticsDemo extends Command
{
    protected $signature = 'ptsp:seed-statistics-demo {--clear : Remove only the statistics demo entries}';
    protected $description = 'Load a clearly labeled, repeatable public statistics preview without creating operational consultations';

    public function handle()
    {
        if ($this->option('clear')) {
            DB::table('statistics_demo_entries')->delete();
            $this->info('Data contoh statistik dihapus. Statistik kembali memakai data layanan.');
            return 0;
        }
        $specs = [
            ['Pengadilan Agama Sorong', [30,66,24], [102,14,4]],
            ['Pengadilan Agama Manokwari', [24,48,18], [77,10,3]],
            ['Pengadilan Agama Kaimana', [12,24,12], [41,6,1]],
            ['Pengadilan Agama Fak-Fak', [12,24,6], [35,6,1]],
            ['Pengadilan Tinggi Agama Papua Barat', [6,10,4], [17,2,1]],
        ];
        $rows = []; $index = 0;
        foreach ($specs as $spec) {
            $unit = WorkUnit::where('name',$spec[0])->first();
            if (!$unit) { $this->error('Satker contoh belum terdaftar: '.$spec[0]); return 1; }
            $local = 0;
            foreach (['meet','chat','mace'] as $channelIndex=>$channel) {
                for ($i=0; $i<$spec[1][$channelIndex]; $i++, $local++, $index++) {
                    $status = $local < $spec[2][0] ? 'completed' : ($local < $spec[2][0]+$spec[2][1] ? 'active' : 'cancelled');
                    $purposeIndex = $index % 20;
                    $purpose = $purposeIndex < 8 ? 'Informasi perkara' : ($purposeIndex < 14 ? 'Persyaratan berperkara'
                        : ($purposeIndex < 17 ? 'Biaya perkara' : ($purposeIndex < 19 ? 'Akta cerai' : 'Layanan lainnya')));
                    $meters = 12000 + ($index % 25) * 2000;
                    $rows[] = ['sample_key'=>'pace-statistics-'.str_pad((string)$index,4,'0',STR_PAD_LEFT),
                        'work_unit_id'=>$unit->id,'channel'=>$channel,'status'=>$status,'purpose'=>$purpose,
                        'distance_meters'=>$meters,'duration_seconds'=>(int)round($meters * 0.09),
                        'fuel_cost'=>(int)round($meters / 1000 / 12 * 10000)];
                }
            }
        }
        DB::transaction(function()use($rows){
            DB::table('statistics_demo_entries')->delete();
            foreach (array_chunk($rows,100) as $chunk) DB::table('statistics_demo_entries')->insert($chunk);
        });
        $this->info(count($rows).' entri contoh statistik tersedia. Seluruh angka ditandai sebagai data contoh.');
        return 0;
    }
}
