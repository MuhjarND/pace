<?php
namespace App\Http\Controllers;

use App\Region;
use App\WorkUnit;
use App\Services\CourtFinder;
use App\Services\FeeEstimator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicPortalController extends Controller
{
    public function index(Request $request, CourtFinder $finder)
    {
        $request->validate(['region'=>($request->has('region') ? 'required' : 'nullable').'|string|exists:regions,code'], [
            'region.required'=>'Pilih provinsi untuk mulai mencari pengadilan.',
            'region.string'=>'Pilihan wilayah tidak valid. Pilih ulang melalui formulir.',
            'region.exists'=>'Wilayah belum terdaftar. Pilih wilayah lain atau hubungi PTSP.',
        ]);
        $region = $request->filled('region') ? Region::findOrFail($request->region) : null;
        $mapping = $region ? $finder->find($region) : null;
        return view('public.finder', compact('region', 'mapping'));
    }

    public function regions(Request $request)
    {
        $request->validate(['parent'=>'nullable|string|exists:regions,code']);
        $q = Region::orderBy('name');
        $request->filled('parent') ? $q->where('parent_code', $request->parent) : $q->whereNull('parent_code');
        return response()->json(['regions'=>$q->get(['code','name'])])->header('Cache-Control', 'public, max-age=3600');
    }

    public function consult(Request $request, CourtFinder $finder)
    {
        $request->validate(['region'=>'nullable|string|exists:regions,code', 'unit'=>'nullable|integer|exists:work_units,id', 'case_number'=>'nullable|string|max:100']);
        $region = $request->filled('region') ? Region::findOrFail($request->region) : null;
        $mapping = $region ? $finder->find($region) : null;
        $units = WorkUnit::where('is_active', true)->orderBy('id')->get();
        $selected = $mapping ? $mapping->work_unit_id : $request->query('unit');
        $unit = $units->firstWhere('id', (int)old('work_unit_id', $selected));
        $caseContext = $unit && \App\Services\CaseStatusLookup::validNumber(\App\Services\CaseStatusLookup::normalize($request->query('case_number')))
            ? \App\Services\CaseStatusLookup::normalize($request->query('case_number')) : null;
        $nonce = $request->session()->get('public_booking_nonce');
        if (!$nonce || $request->boolean('new')) { $nonce = Str::random(48); $request->session()->put('public_booking_nonce', $nonce); }
        $localNow = now()->timezone(config('ptsp.timezone'));
        $tickets = \App\GuestTicket::whereIn('id', array_keys($request->session()->get('guest_tickets', [])))
            ->where('expires_at', '>', now())->with('meeting', 'room')->orderByDesc('id')->take(10)->get();
        return response()->view('public.consult', compact('region', 'mapping', 'units', 'unit', 'nonce', 'localNow', 'tickets', 'caseContext'))->header('Cache-Control', 'no-store');
    }

    public function availability(Request $request, $id)
    {
        $data = $request->validate(['date'=>'required|date_format:Y-m-d']);
        $unit = WorkUnit::findOrFail($id);
        $date = Carbon::createFromFormat('!Y-m-d', $data['date'], config('ptsp.timezone'));
        $result = app(\App\Services\ServiceHoursService::class)->availability($unit, $date);
        if (!\App\ConsultationSetting::where('work_unit', $unit->name)->whereNotNull('join_url')->exists()) {
            $result = array_merge($result, ['slots'=>[], 'can_now'=>false, 'message'=>'Tautan Google Meet belum disiapkan satker. Anda dapat menggunakan Chat PTSP.']);
        }
        return response()->json($result)->header('Cache-Control', 'no-store');
    }

    public function fees(Request $request)
    {
        $request->validate(['region'=>'nullable|string|exists:regions,code', 'unit'=>'nullable|integer|exists:work_units,id']);
        $region = $request->filled('region') ? Region::findOrFail($request->region) : null;
        $mapping = $region ? app(CourtFinder::class)->find($region) : null;
        $unit = $mapping ? $mapping->unit : ($request->filled('unit') ? WorkUnit::findOrFail($request->unit) : null);
        $units = WorkUnit::where('is_active', true)->orderBy('id')->get();
        $estimate = $request->session()->get('fee_estimate');
        if ($estimate && $unit && (int)$estimate['work_unit_id'] !== (int)$unit->id) $estimate = null;
        return view('public.fees', compact('region', 'unit', 'units', 'estimate'));
    }

    public function estimate(Request $request, FeeEstimator $estimator)
    {
        $data = $request->validate([
            'work_unit_id'=>'required|integer|exists:work_units,id', 'case_type'=>['required', Rule::in(array_keys(config('ptsp.case_types')))],
            'parties'=>'required|array|min:1|max:10', 'parties.*.role'=>'required|in:applicant,respondent',
            'parties.*.region_code'=>'required|string|exists:regions,code',
        ]);
        if (!collect($data['parties'])->contains('role', 'applicant')) {
            throw \Illuminate\Validation\ValidationException::withMessages(['parties'=>'Sedikitnya satu pemohon / penggugat diperlukan.']);
        }
        if (in_array($data['case_type'], ['cerai_gugat', 'cerai_talak']) && !collect($data['parties'])->contains('role', 'respondent')) {
            throw \Illuminate\Validation\ValidationException::withMessages(['parties'=>'Tambahkan pihak termohon / tergugat untuk perkara ini.']);
        }
        foreach ($data['parties'] as $i=>$party) {
            if ((int)Region::findOrFail($party['region_code'])->level !== 4) {
                throw \Illuminate\Validation\ValidationException::withMessages(['parties.'.$i.'.region_code'=>'Pilih sampai kampung / kelurahan untuk menentukan radius biaya.']);
            }
        }
        $unit = WorkUnit::findOrFail($data['work_unit_id']);
        $result = $estimator->estimate($unit, $data['case_type'], $data['parties']);
        $request->session()->put('fee_context', ['work_unit_id'=>$unit->id, 'case_type'=>$data['case_type'], 'parties'=>$data['parties'],
            'total'=>$result['total'], 'schedule_id'=>optional($result['schedule'])->id, 'calculated_at'=>now()->toIso8601String()]);
        $request->session()->put('fee_estimate', ['available'=>$result['available'], 'message'=>$result['message'], 'total'=>$result['total'],
            'lines'=>$result['lines'], 'unit'=>$unit->name, 'work_unit_id'=>$unit->id, 'case_type'=>$data['case_type'], 'parties'=>$data['parties'],
            'source_title'=>optional($result['schedule'])->source_title, 'source_url'=>optional($result['schedule'])->source_url,
            'effective_date'=>optional(optional($result['schedule'])->effective_date)->format('d/m/Y')]);
        return redirect()->route('public.fees', ['unit'=>$unit->id])->with('success', 'Hasil perkiraan biaya telah diperiksa.');
    }
}
