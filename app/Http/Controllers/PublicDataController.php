<?php
namespace App\Http\Controllers;

use App\AdminActivityLog;
use App\CourtTerritory;
use App\FeeRadius;
use App\FeeSchedule;
use App\Region;
use App\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicDataController extends Controller
{
    public function __construct() { $this->middleware('auth'); }
    private function actor() { $u = auth()->user(); abort_unless($u->isAdmin() || $u->isOperator(),403); return $u; }
    private function unit($id) { $u=$this->actor(); $unit=WorkUnit::findOrFail($id); abort_unless($u->canManageMasterData() || $u->work_unit === $unit->name,403); return $unit; }
    public function index(Request $request)
    {
        $actor = $this->actor();
        $request->validate(['unit'=>'nullable|integer|exists:work_units,id','edit'=>'nullable|integer|exists:fee_schedules,id','q'=>'nullable|string|max:100']);
        $units = $actor->canManageMasterData() ? WorkUnit::orderBy('id')->get() : WorkUnit::where('name',$actor->work_unit)->get();
        $unit = $request->filled('unit') ? $this->unit($request->unit) : $units->first(); abort_unless($unit,404);
        $query = CourtTerritory::with('unit','region')->orderBy('region_code');
        if ($request->filled('q')) $query->whereHas('region',function($q)use($request){$q->where('name','like','%'.$request->q.'%')->orWhere('code',$request->q);});
        $maps = $query->paginate(15,['*'],'map_page')->appends($request->except('map_page'));
        $schedules = FeeSchedule::where('work_unit_id',$unit->id)->orderByDesc('id')->paginate(10,['*'],'fee_page')->appends($request->except('fee_page'));
        $editing = $request->filled('edit') ? FeeSchedule::where('work_unit_id',$unit->id)->with('radii.region')->findOrFail($request->edit) : null;
        $fonnteError = app(\App\Services\FonnteService::class)->configurationError();
        return view('operations.public-data',compact('units','unit','maps','schedules','editing','actor','fonnteError'));
    }
    public function territory(Request $request)
    {
        abort_unless($this->actor()->canManageMasterData(),403);
        $data=$request->validate(['region_code'=>'required|exists:regions,code','work_unit_id'=>'required|integer|exists:work_units,id',
            'source_title'=>'required|string|max:255','source_url'=>'required|url|max:1000|regex:~\Ahttps://~',
            'effective_date'=>'required|date_format:Y-m-d','is_verified'=>'required|boolean']);
        DB::transaction(function()use($data){
            $map=CourtTerritory::updateOrCreate(['region_code'=>$data['region_code']],array_merge($data,['verified_by'=>$data['is_verified']?auth()->id():null]));
            AdminActivityLog::create(['actor_id'=>auth()->id(),'action'=>'territory.saved','target_type'=>'territory','target_id'=>$map->id,'changes'=>$data]);
        });
        return redirect()->route('operations.public-data')->with('success','Pemetaan wilayah disimpan. Hanya pemetaan terverifikasi yang digunakan pencarian publik.');
    }
    public function tariff(Request $request)
    {
        $data=$request->validate(['work_unit_id'=>'required|integer|exists:work_units,id','id'=>'nullable|integer|exists:fee_schedules,id',
            'case_type'=>['required',Rule::in(array_keys(config('ptsp.case_types')))], 'source_title'=>'required|string|max:255',
            'source_url'=>'required|url|max:1000|regex:~\Ahttps://~','effective_date'=>'required|date_format:Y-m-d','expires_on'=>'nullable|date_format:Y-m-d|after_or_equal:effective_date',
            'is_published'=>'required|boolean','components'=>'required|array|min:1|max:30','components.*.label'=>'required|string|max:100',
            'components.*.basis'=>'required|in:fixed,applicant,respondent,radius_applicant,radius_respondent',
            'components.*.amount'=>'required|integer|min:0|max:100000000','components.*.quantity'=>'required|integer|min:1|max:100',
            'radii'=>'nullable|string|max:150000']);
        $unit=$this->unit($data['work_unit_id']); $rows=[];
        foreach (preg_split('/\R/',trim($data['radii']??'')) as $i=>$line) {
            if (!trim($line))continue;
            $parts=array_map('trim',explode('|',$line));
            if(count($parts)!==3 || !Region::whereKey($parts[0])->exists() || !preg_match('/\A[0-9]{1,9}\z/',$parts[2]) || !$parts[1] || mb_strlen($parts[1])>100 || isset($rows[$parts[0]])) {
                throw ValidationException::withMessages(['radii'=>'Periksa baris radius '.($i+1).': kode wilayah | nama radius | biaya rupiah bulat. Kode tidak boleh berulang.']);
            }
            $rows[$parts[0]]=['region_code'=>$parts[0],'label'=>$parts[1],'amount'=>(int)$parts[2]];
        }
        if ($data['is_published'] && collect($data['components'])->contains(function($c){return strpos($c['basis'],'radius_')===0;}) && !$rows) {
            throw ValidationException::withMessages(['radii'=>'Isi radius sesuai SK sebelum mempublikasikan tarif panggilan.']);
        }
        $schedule=DB::transaction(function()use($data,$unit,$rows){
            WorkUnit::whereKey($unit->id)->lockForUpdate()->firstOrFail();
            $s=!empty($data['id'])?FeeSchedule::where('work_unit_id',$unit->id)->whereKey($data['id'])->lockForUpdate()->firstOrFail():new FeeSchedule;
            $s->fill(collect($data)->only(['work_unit_id','case_type','source_title','source_url','effective_date','expires_on','is_published','components'])->all());
            $s->verified_by=$data['is_published']?auth()->id():null; $s->save();
            // Replace the explicitly submitted complete radius list atomically, keeping other schedules intact.
            $s->radii()->delete(); foreach($rows as $r) $s->radii()->create($r);
            AdminActivityLog::create(['actor_id'=>auth()->id(),'action'=>'tariff.saved','target_type'=>'fee_schedule','target_id'=>$s->id,
                'changes'=>['published'=>$s->is_published,'source'=>$s->source_title,'components'=>$s->components,'radius_count'=>count($rows)]]);
            return $s;
        });
        return redirect()->route('operations.public-data',['unit'=>$unit->id,'edit'=>$schedule->id])->with('success','Tarif disimpan'.($schedule->is_published?' dan dipublikasikan.':' sebagai draft.'));
    }
}
