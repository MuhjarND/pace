<?php

namespace App\Http\Controllers;

use App\Complaint;
use App\Services\ComplaintService;
use App\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ComplaintManagementController extends Controller
{
    private function authorizeReader()
    {
        abort_unless(auth()->user() && auth()->user()->canHandleComplaints(),403);
    }

    public function index(Request $request)
    {
        $this->authorizeReader();
        $data=$request->validate(['status'=>['nullable',Rule::in(array_keys(Complaint::STATUSES))],
            'type'=>['nullable',Rule::in(array_keys(Complaint::TYPES))],'unit'=>'nullable|integer|exists:work_units,id','q'=>'nullable|string|max:40']);
        $query=Complaint::with('unit')->orderByDesc('id');
        foreach (['status'=>'status','type'=>'type','unit'=>'work_unit_id'] as $input=>$column) {
            if ($request->filled($input)) $query->where($column,$data[$input]);
        }
        if ($request->filled('q')) $query->where('reference',strtoupper(trim($data['q'])));
        $complaints=$query->paginate(15)->appends($request->except('page'));
        $counts=Complaint::select('status',DB::raw('COUNT(*) AS total'))->groupBy('status')->pluck('total','status');
        $units=WorkUnit::orderBy('name')->get();
        return $this->page('complaints.index',compact('complaints','counts','units'));
    }

    public function show($reference)
    {
        $this->authorizeReader();
        $complaint=Complaint::where('reference',$reference)->with('unit','attachments','assignee')->firstOrFail();
        $events=$complaint->events()->with('actor')->orderBy('id')->get();
        return $this->page('complaints.show',compact('complaint','events'));
    }

    public function update(Request $request, $reference)
    {
        $this->authorizeReader();
        $data=$request->validate(['status'=>['required',Rule::in(array_keys(Complaint::STATUSES))],
            'lock_version'=>'required|integer|min:1','public_message'=>'required|string|min:5|max:3000',
            'internal_note'=>'nullable|string|max:5000'],
            ['required'=>'Lengkapi :attribute.','string'=>'Isian :attribute harus berupa teks.','integer'=>'Versi laporan tidak valid. Muat ulang halaman.',
                'min'=>'Isian :attribute belum memenuhi batas minimal :min.','max'=>'Isian :attribute melebihi batas maksimal :max karakter.',
                'in'=>'Pilih status laporan yang tersedia.'],
            ['public_message'=>'pesan untuk pelapor','internal_note'=>'catatan internal','status'=>'status laporan','lock_version'=>'versi laporan']);
        DB::transaction(function()use($data,$reference){
            $complaint=Complaint::where('reference',$reference)->lockForUpdate()->firstOrFail();
            if ((int)$complaint->lock_version !== (int)$data['lock_version']) {
                throw ValidationException::withMessages(['lock_version'=>'Laporan sudah diperbarui pengelola lain. Muat ulang halaman sebelum menyimpan; salin dahulu pesan yang sedang Anda tulis.']);
            }
            $allowed=['received'=>['received','reviewing','follow_up','completed'],
                'reviewing'=>['reviewing','follow_up','completed'],'follow_up'=>['follow_up','completed','reviewing'],
                'completed'=>['completed','reviewing']];
            if (!in_array($data['status'],$allowed[$complaint->status],true)) {
                throw ValidationException::withMessages(['status'=>'Pilih status yang sesuai. Laporan selesai dapat dibuka kembali sebagai Ditelaah.']);
            }
            $complaint->status=$data['status'];
            if (!$complaint->assigned_to) $complaint->assigned_to=auth()->id();
            $complaint->lock_version++;
            $complaint->save();
            $complaint->events()->create(['actor_id'=>auth()->id(),'status'=>$data['status'],
                'public_message'=>$data['public_message'],'internal_note'=>$data['internal_note'] ?? null]);
        });
        return redirect()->route('complaints.show',$reference)->with('success','Tindak lanjut tersimpan. Pesan untuk pelapor dapat dilihat melalui tiket pengaduan.');
    }

    public function attachment($reference, $id, ComplaintService $service)
    {
        $this->authorizeReader();
        $complaint=Complaint::where('reference',$reference)->firstOrFail();
        return $service->download($complaint->attachments()->findOrFail($id));
    }

    private function page($view, $data)
    {
        return response()->view($view,$data)->header('Cache-Control','no-store, private')
            ->header('X-Robots-Tag','noindex, nofollow')->header('Referrer-Policy','no-referrer');
    }
}
