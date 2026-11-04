<?php

namespace App\Http\Controllers;

use App\Complaint;
use App\Services\ComplaintService;
use App\WorkUnit;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicComplaintController extends Controller
{
    public function create(Request $request)
    {
        $keys = array_filter($request->session()->get('complaint_form_keys', []), function ($expires) { return $expires > now()->timestamp; });
        $nonce = Str::random(48);
        $keys[$nonce] = now()->timestamp+7200;
        $request->session()->put('complaint_form_keys', array_slice($keys, -10, null, true));
        $units = WorkUnit::orderBy('id')->get();
        $unit = $units->firstWhere('id', (int)$request->query('unit'));
        return $this->page('public.complaints.create', compact('nonce','units','unit'));
    }

    public function store(Request $request, ComplaintService $service, RateLimiter $limiter)
    {
        $this->limit($request,$limiter,'submit',5,600);
        $data = $request->validate([
            'request_key'=>'required|string|size:48', 'work_unit_id'=>['required','integer',Rule::exists('work_units','id')],
            'type'=>['required',Rule::in(array_keys(Complaint::TYPES))], 'subject'=>'required|string|min:5|max:150',
            'occurred_on'=>'required|date_format:Y-m-d|before_or_equal:'.now()->timezone(config('ptsp.timezone'))->toDateString(),
            'location'=>'nullable|string|max:200', 'reported_person'=>'nullable|string|max:150',
            'description'=>'required|string|min:30|max:10000',
            'evidence'=>'nullable|array|max:3',
            'evidence.*'=>'required|file|max:5120|mimes:pdf,jpg,jpeg,png|mimetypes:application/pdf,image/jpeg,image/png',
            'consent'=>'required|accepted',
        ], [
            'required'=>'Lengkapi :attribute.', 'string'=>'Isian :attribute harus berupa teks.',
            'min'=>'Isian :attribute belum memenuhi batas minimal :min karakter.',
            'max'=>'Isian :attribute melebihi batas maksimal :max karakter.',
            'integer'=>'Pilih satuan kerja yang terdaftar.', 'exists'=>'Pilih satuan kerja yang terdaftar.',
            'in'=>'Pilih jenis laporan yang tersedia.', 'date_format'=>'Gunakan tanggal kejadian yang lengkap dan valid.',
            'accepted'=>'Setujui tindak lanjut laporan sebelum mengirim.',
            'evidence.array'=>'Pilih bukti melalui kolom unggah berkas.',
            'evidence.*.file'=>'Berkas bukti belum dapat diunggah. Pilih ulang berkas sebelum mencoba lagi.',
            'evidence.max'=>'Lampirkan paling banyak 3 berkas bukti.',
            'evidence.*.max'=>'Setiap berkas bukti maksimal 5 MB.',
            'evidence.*.mimes'=>'Bukti hanya boleh berupa PDF, JPG, atau PNG.',
            'evidence.*.mimetypes'=>'Isi berkas bukti harus berupa PDF, JPG, atau PNG.',
            'occurred_on.before_or_equal'=>'Tanggal kejadian tidak boleh melebihi hari ini (WIT).',
        ], ['description'=>'kronologi','subject'=>'judul laporan','occurred_on'=>'tanggal kejadian',
            'work_unit_id'=>'satuan kerja','consent'=>'persetujuan','type'=>'jenis laporan','request_key'=>'formulir pengaduan',
            'location'=>'tempat kejadian','reported_person'=>'pihak terkait']);
        abort_unless(($request->session()->get('complaint_form_keys', [])[$data['request_key']] ?? 0) > now()->timestamp,419);
        try {
            $result = $service->submit($data, $request->file('evidence', []));
        } catch (ValidationException $e) { throw $e; }
        catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Complaint submission did not complete.', ['exception_type'=>get_class($e)]);
            throw ValidationException::withMessages(['submission'=>'Pengaduan belum dapat disimpan. Coba lagi; pilih ulang lampiran jika ada. Isian laporan tetap tersedia.']);
        }
        $this->grant($request,$result['complaint']);
        $redirect = redirect()->route('public.complaints.show',$result['complaint']->reference)
            ->with('success','Pengaduan tersimpan dan masuk ke kotak masuk pengelola pengaduan PTA Papua Barat.');
        if ($result['secret']) $redirect->with('complaint_receipt',['reference'=>$result['complaint']->reference,'secret'=>$result['secret']]);
        return $redirect;
    }

    public function track()
    {
        return $this->page('public.complaints.track');
    }

    public function unlock(Request $request, RateLimiter $limiter)
    {
        $this->limit($request,$limiter,'unlock',10,60);
        $data = $request->validate(['reference'=>'required|string|max:40','access_code'=>'required|string|max:64'],
            ['required'=>'Lengkapi :attribute.','string'=>'Isian :attribute tidak valid.','max'=>'Isian :attribute terlalu panjang.'],
            ['reference'=>'nomor laporan','access_code'=>'kode rahasia']);
        $reference = strtoupper(trim($data['reference']));
        $complaint = preg_match('/\AADU-[0-9]{8}-[A-Z0-9]{8}\z/',$reference) ? Complaint::where('reference',$reference)->first() : null;
        $hash = $complaint ? $complaint->access_hash : str_repeat('0',64);
        $matches=hash_equals($hash,hash('sha256',$data['access_code']));
        if (!$complaint || !$matches) {
            throw ValidationException::withMessages(['access_code'=>'Nomor laporan atau kode rahasia tidak cocok. Periksa kedua isian lalu coba lagi.']);
        }
        $this->grant($request,$complaint);
        return redirect()->route('public.complaints.show',$complaint->reference);
    }

    public function show(Request $request, $reference)
    {
        $complaint = $this->own($request,$reference)->load('unit','attachments');
        // Internal notes and handler names are never loaded for the reporter view.
        $events = $complaint->events()->orderBy('id')->get(['id','complaint_id','status','public_message','created_at']);
        return $this->page('public.complaints.show',compact('complaint','events'));
    }

    public function attachment(Request $request, $reference, $id, ComplaintService $service)
    {
        $complaint = $this->own($request,$reference);
        return $service->download($complaint->attachments()->findOrFail($id));
    }

    public function lock(Request $request, $reference)
    {
        $this->own($request,$reference);
        $grants=$request->session()->get('complaint_grants',[]);unset($grants[$reference]);
        $request->session()->put('complaint_grants',$grants);
        if ($request->session()->get('complaint_receipt.reference')===$reference) $request->session()->forget('complaint_receipt');
        return redirect()->route('public.complaints.track')->with('success','Akses laporan pada perangkat ini ditutup. Laporan tetap tersimpan; buka kembali menggunakan nomor laporan dan kode rahasia.');
    }

    private function grant(Request $request, Complaint $complaint)
    {
        $owned = array_filter($request->session()->get('complaint_grants',[]),function($expiry){return $expiry>now()->timestamp;});
        $owned[$complaint->reference] = now()->timestamp+7200;
        $request->session()->put('complaint_grants',array_slice($owned,-30,null,true));
    }

    private function own(Request $request, $reference)
    {
        abort_unless(($request->session()->get('complaint_grants',[])[$reference] ?? 0) > now()->timestamp,404);
        return Complaint::where('reference',$reference)->firstOrFail();
    }

    private function limit(Request $request, RateLimiter $limiter, $kind, $max, $seconds)
    {
        $key='complaint-'.$kind.':'.hash('sha256',(string)$request->ip());
        if ($limiter->tooManyAttempts($key,$max)) {
            throw ValidationException::withMessages(['rate_limit'=>'Terlalu banyak percobaan. Coba lagi dalam '.$limiter->availableIn($key).' detik. Isian Anda tetap tersimpan.']);
        }
        $limiter->hit($key,$seconds);
    }

    private function page($view, $data = [])
    {
        return response()->view($view,$data)->header('Cache-Control','no-store, private')
            ->header('X-Robots-Tag','noindex, nofollow')->header('Referrer-Policy','no-referrer');
    }
}
