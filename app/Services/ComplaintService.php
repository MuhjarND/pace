<?php

namespace App\Services;

use App\Complaint;
use App\WorkUnit;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ComplaintService
{
    public function submit(array $data, array $files)
    {
        $hash = hash('sha256', $data['request_key']);
        $stored = [];
        $secret = Str::random(32);
        try {
            $result = DB::transaction(function () use ($data, $files, $hash, $secret, &$stored) {
                $unit = WorkUnit::whereKey($data['work_unit_id'])->lockForUpdate()->firstOrFail();
                if ($existing = Complaint::where('request_hash', $hash)->first()) return ['complaint'=>$existing,'secret'=>null];
                // Closing consultation intake must not prevent reports about that work unit.
                $complaint = Complaint::create([
                    'reference'=>'ADU-'.now()->timezone(config('ptsp.timezone'))->format('Ymd').'-'.strtoupper(Str::random(8)),
                    'request_hash'=>$hash, 'access_hash'=>hash('sha256', $secret), 'work_unit_id'=>$unit->id,
                    'type'=>$data['type'], 'subject'=>$data['subject'], 'description'=>$data['description'],
                    'occurred_on'=>$data['occurred_on'], 'location'=>$data['location'] ?? null,
                    'reported_person'=>$data['reported_person'] ?? null,
                    // Do not derive reporter identity from login, contact fields, or browser payloads.
                    'reporter_name'=>null, 'reporter_contact'=>null, 'is_anonymous'=>true,
                    'consented_at'=>now(), 'status'=>'received',
                ]);
                foreach ($files as $i=>$file) {
                    $mime = $file->getMimeType();
                    $extension = ['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'][$mime] ?? null;
                    if (!$extension) throw \Illuminate\Validation\ValidationException::withMessages(['evidence'=>'Jenis bukti tidak didukung.']);
                    $bytes = file_get_contents($file->getRealPath());
                    $path = 'private/complaints/'.Str::uuid().'.enc';
                    $stored[] = $path;
                    if (!Storage::disk('local')->put($path, Crypt::encryptString(base64_encode($bytes)))) {
                        throw new \RuntimeException('Bukti pengaduan belum dapat disimpan.');
                    }
                    $complaint->attachments()->create(['path'=>$path,'name'=>'Bukti '.($i+1).'.'.$extension,
                        'mime'=>$mime,'bytes'=>strlen($bytes),'sha256'=>hash('sha256',$bytes)]);
                }
                $complaint->events()->create(['status'=>'received','public_message'=>'Pengaduan tercatat dan masuk ke kotak masuk pengelola pengaduan PTA Papua Barat.']);
                return ['complaint'=>$complaint,'secret'=>$secret];
            });
            return $result;
        } catch (\Throwable $error) {
            if ($error instanceof \Illuminate\Database\QueryException) {
                try {
                    $existing = Complaint::where('request_hash',$hash)->first();
                } catch (\Throwable $lookupFailure) {
                    // Preserve encrypted evidence if commit outcome cannot be determined.
                    throw $error;
                }
                if ($existing) {
                    $committedHere = hash_equals($existing->access_hash,hash('sha256',$secret));
                    if (!$committedHere) foreach ($stored as $path) Storage::disk('local')->delete($path);
                    return ['complaint'=>$existing,'secret'=>$committedHere ? $secret : null];
                }
            }
            foreach ($stored as $path) Storage::disk('local')->delete($path);
            throw $error;
        }
    }

    public function download($attachment)
    {
        try {
            $data = Storage::disk('local')->get($attachment->path);
            $bytes = base64_decode(Crypt::decryptString($data), true);
            if ($bytes === false || !hash_equals($attachment->sha256, hash('sha256', $bytes))) throw new \RuntimeException;
        } catch (\Throwable $e) {
            abort(503, 'Bukti belum dapat dibuka. Hubungi pengelola pengaduan PTA.');
        }
        return response($bytes)->header('Content-Type',$attachment->mime)
            ->header('Content-Disposition','attachment; filename="'.$attachment->name.'"')
            ->header('X-Content-Type-Options','nosniff')->header('Cache-Control','no-store, private')
            ->header('Referrer-Policy','no-referrer');
    }
}
