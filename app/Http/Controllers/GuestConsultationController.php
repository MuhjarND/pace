<?php
namespace App\Http\Controllers;

use App\ChatMessage;
use App\ConsultationSetting;
use App\GuestTicket;
use App\Meeting;
use App\Region;
use App\Rules\GoogleMeetUrl;
use App\Services\ChatRoutingService;
use App\Services\ConsultationQueueService;
use App\Services\CourtFinder;
use App\Services\ServiceHoursService;
use App\User;
use App\WorkUnit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuestConsultationController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'request_key'=>'required|string|size:48', 'work_unit_id'=>'required|integer|exists:work_units,id',
            'region_code'=>'nullable|string|exists:regions,code', 'name'=>'required|string|min:2|max:100', 'channel'=>'required|in:chat,meet,mace',
            'phone'=>['nullable','string','regex:/\A\+?[0-9 ()-]{9,22}\z/'], 'topic'=>'required|string|min:3|max:255',
            'consent'=>'required|accepted', 'consultation_date'=>'required_if:channel,meet|nullable|date_format:Y-m-d',
            'consultation_time'=>['required_if:channel,meet','nullable','regex:/\A(now|([01][0-9]|2[0-3]):[0-5][0-9])\z/'],
            'origin_region_code'=>'nullable|string|exists:regions,code',
        ]);
        abort_unless(hash_equals((string)$request->session()->get('public_booking_nonce'), $data['request_key']), 419);
        $originRegion = !empty($data['origin_region_code']) ? Region::findOrFail($data['origin_region_code']) : null;
        if ($originRegion && (int)$originRegion->level !== 4) {
            throw ValidationException::withMessages(['origin_region_code'=>'Pilih wilayah domisili sampai kampung / kelurahan.']);
        }
        $requestKey = hash('sha256', $data['request_key']);
        $phone = preg_replace('/[^0-9]/', '', $data['phone'] ?? '');
        if (strpos($phone, '0') === 0) $phone = '62'.substr($phone, 1);
        if (($phone || $data['channel'] === 'meet') && !preg_match('/\A628[0-9]{7,11}\z/', $phone)) {
            throw ValidationException::withMessages(['phone'=>'Masukkan nomor WhatsApp Indonesia, misalnya 0812… atau 62812….']);
        }
        $start = $data['channel'] === 'mace' ? now()->copy() : null;
        if ($data['channel'] === 'meet') {
            $zone = config('ptsp.timezone'); $today = now()->timezone($zone);
            $date = Carbon::createFromFormat('!Y-m-d', $data['consultation_date'], $zone);
            if ($date->lt($today->copy()->startOfDay()) || $date->gt($today->copy()->startOfDay()->addDays(config('ptsp.consultation_booking_days')))
                || ($data['consultation_time'] === 'now' && !$date->isSameDay($today))) {
                throw ValidationException::withMessages(['consultation_date'=>'Pilih tanggal yang tersedia. Sekarang hanya berlaku untuk hari ini.']);
            }
            $start = $data['consultation_time'] === 'now' ? now()->copy() : Carbon::createFromFormat('!Y-m-d H:i', $data['consultation_date'].' '.$data['consultation_time'], $zone)->utc();
        }
        try {
        $ticket = DB::transaction(function () use ($data, $phone, $start, $requestKey, $request, $originRegion) {
            $unit = WorkUnit::whereKey($data['work_unit_id'])->lockForUpdate()->firstOrFail();
            if ($old = GuestTicket::where('request_key', $requestKey)->first()) return $old;
            abort_unless($unit->is_active, 422, 'Satker tidak menerima konsultasi baru.');
            if (!empty($data['region_code'])) {
                $mapping = app(CourtFinder::class)->find(Region::findOrFail($data['region_code']));
                if ($mapping && (int)$mapping->work_unit_id !== (int)$unit->id) throw ValidationException::withMessages(['work_unit_id'=>'Pilih PTSP pengadilan yang melayani wilayah Anda.']);
            }
            $setting = null;
            if ($start) {
                if ($data['channel'] === 'meet') {
                    $setting = ConsultationSetting::where('work_unit', $unit->name)->lockForUpdate()->first();
                    if (!$setting || !GoogleMeetUrl::normalize($setting->join_url)) throw ValidationException::withMessages(['work_unit_id'=>'Tautan Google Meet belum disiapkan satker. Pilih Chat PTSP.']);
                }
                try {
                    app(ServiceHoursService::class)->assertBookable($unit, $start, $data['channel'] === 'mace' || $data['consultation_time'] === 'now');
                } catch (ValidationException $e) {
                    if ($data['channel'] !== 'mace') throw $e;
                    throw ValidationException::withMessages(['channel'=>'Antrean Mace belum tersedia. '.$e->validator->errors()->first()]);
                }
            }
            // A fresh principal per ticket preserves legacy staff chat/meeting relations without phone login or history reuse.
            $user = new User(['name'=>$data['name'], 'email'=>Str::uuid().'@ticket.invalid', 'phone'=>null,
                'password'=>Hash::make(Str::random(64)), 'role'=>'user', 'can_monitor'=>false, 'work_unit'=>$unit->name]);
            $user->forceFill(['is_guest'=>true])->save();
            $secret = Str::random(64);
            $ticket = new GuestTicket(['public_id'=>Str::random(24), 'request_key'=>$requestKey, 'user_id'=>$user->id,
                'phone'=>$phone ?: null, 'region_code'=>$data['region_code'] ?? null, 'consented_at'=>now(),
                'invite_hash'=>$data['channel'] === 'meet' ? hash('sha256', $secret) : null, 'invite_secret'=>$data['channel'] === 'meet' ? Crypt::encryptString($secret) : null,
                'expires_at'=>($start ?: now())->copy()->addDays(7),
                'context'=>(int)$request->session()->get('fee_context.work_unit_id') === (int)$unit->id ? $request->session()->get('fee_context') : null]);
            if ($start) {
                $date = $start->copy()->timezone(config('ptsp.timezone'))->toDateString();
                $number = (int)Meeting::where('work_unit', $unit->name)->where('queue_date', $date)->max('queue_number') + 1;
                $url = $setting ? GoogleMeetUrl::normalize($setting->join_url) : null;
                $meeting = Meeting::create(['user_id'=>$user->id, 'work_unit'=>$unit->name, 'channel'=>$data['channel'], 'topic'=>$data['topic'], 'start_time'=>$start,
                    'duration'=>config('ptsp.consultation_duration_minutes'), 'join_url'=>$url, 'meeting_id'=>$url ? basename(parse_url($url, PHP_URL_PATH)) : null,
                    'status'=>'scheduled', 'verification_status'=>$start->lte(now()) ? 'immediate' : 'pending', 'queue_date'=>$date, 'queue_number'=>$number]);
                $ticket->meeting_id = $meeting->id;
            } else {
                $room = app(ChatRoutingService::class)->openFor($user);
                ChatMessage::create(['chat_room_id'=>$room->id, 'sender_id'=>$user->id, 'message'=>$data['topic'], 'type'=>'text']);
                $ticket->chat_room_id = $room->id;
            }
            $ticket->save();
            app(\App\Services\JourneyRoutingService::class)->record($ticket, $unit, $originRegion);
            return $ticket;
        });
        } catch (\Illuminate\Database\QueryException $e) {
            $ticket = GuestTicket::where('request_key',$requestKey)->first();
            if (!$ticket) throw $e;
        }
        $owned = $request->session()->get('guest_tickets', []);
        $owned[$ticket->id] = $ticket->public_id;
        $request->session()->put('guest_tickets', $owned);
        // External routing runs after commit, without holding the court's booking lock.
        if ($ticket->journey) app(\App\Services\JourneyRoutingService::class)->resolve($ticket->journey);
        // Keep the nonce until the next explicit new request so browser retries remain idempotent.
        return redirect()->route('public.ticket', $ticket->public_id)->with('success', 'Konsultasi tersimpan. Simpan halaman tiket ini untuk memantau layanan.');
    }

    private function own(Request $request, $id)
    {
        $ticket = GuestTicket::where('public_id', $id)->firstOrFail();
        abort_unless(($request->session()->get('guest_tickets', [])[$ticket->id] ?? null) === $ticket->public_id, 404);
        abort_if($ticket->expires_at->lte(now()), 410, 'Tiket konsultasi sudah kedaluwarsa. Silakan ajukan konsultasi baru.');
        return $ticket;
    }

    public function show(Request $request, $id)
    {
        $ticket = $this->own($request, $id)->load('user','meeting','room','notification');
        $queue = $ticket->meeting ? app(ConsultationQueueService::class)->state($ticket->meeting) : null;
        $unit = $ticket->meeting && $ticket->meeting->isMace() ? WorkUnit::where('name', $ticket->user->work_unit)->first() : null;
        $messages = $ticket->room ? $ticket->room->messages()->with('sender')->orderByDesc('id')->limit(100)->get()->reverse() : collect();
        return response()->view('public.ticket', compact('ticket','queue','messages','unit'))->header('Cache-Control','no-store')->header('Referrer-Policy','no-referrer');
    }

    public function state(Request $request, $id)
    {
        $ticket = $this->own($request, $id)->load('meeting','notification');
        $state = $ticket->meeting ? app(ConsultationQueueService::class)->state($ticket->meeting) : null;
        return response()->json(['queue'=>$state, 'verification'=>optional($ticket->meeting)->verificationLabel(),
            'called'=>(bool)optional($ticket->meeting)->called_at, 'notification'=>optional($ticket->notification)->statusLabel(),
            'can_join'=>$ticket->inviteActive() && (bool)$ticket->meeting->called_at && $state['ready']])->header('Cache-Control','no-store');
    }

    public function messages(Request $request, $id)
    {
        $request->validate(['after'=>'nullable|integer|min:0', 'before'=>'nullable|integer|min:1']);
        $ticket = $this->own($request, $id); abort_unless($ticket->chat_room_id, 404);
        $q = ChatMessage::where('chat_room_id',$ticket->chat_room_id)->with('sender');
        if ($request->filled('before')) $q->where('id','<',$request->before);
        $q->where('id','>',(int)$request->input('after',0));
        $rows = $request->filled('before') ? $q->orderByDesc('id')->limit(50)->get()->reverse()->values() : $q->orderBy('id')->limit(50)->get();
        ChatMessage::where('chat_room_id',$ticket->chat_room_id)->where('sender_id','!=',$ticket->user_id)->where('id','<=',(int)$rows->max('id'))->update(['is_read'=>true]);
        return response()->json(['messages'=>$rows->map(function ($m) use ($ticket) { return ['id'=>$m->id,'text'=>$m->message,
            'mine'=>(int)$m->sender_id === (int)$ticket->user_id,'name'=>(int)$m->sender_id === (int)$ticket->user_id ? 'Anda' : 'Petugas PTSP',
            'time'=>$m->created_at->timezone(config('ptsp.timezone'))->format('d/m H:i').' WIT']; })])->header('Cache-Control','no-store');
    }

    public function send(Request $request, $id)
    {
        $data = $request->validate(['message'=>'required|string|max:5000']);
        $ticket = $this->own($request, $id)->load('room');
        abort_unless($ticket->room && $ticket->room->status === 'open', 422);
        ChatMessage::create(['chat_room_id'=>$ticket->room->id,'sender_id'=>$ticket->user_id,'message'=>$data['message'],'type'=>'text']);
        $ticket->room->touch();
        return redirect()->route('public.ticket', $ticket->public_id)->with('success','Pesan dikirim ke PTSP satker Anda.');
    }

    private function invitation($token)
    {
        abort_unless(preg_match('/\A[A-Za-z0-9]{64}\z/',$token), 404);
        $ticket = GuestTicket::where('invite_hash',hash('sha256',$token))->with('meeting')->firstOrFail();
        abort_unless($ticket->inviteActive(),410,'Undangan sudah tidak berlaku. Konsultasi selesai, dibatalkan, atau tiket kedaluwarsa.');
        return $ticket;
    }

    public function invitationPage($token)
    {
        $ticket = $this->invitation($token);
        $meeting = $ticket->meeting; $queue = app(ConsultationQueueService::class)->state($meeting);
        // GET has no side effects: link previews cannot start a consultation or consume a token.
        return response()->view('public.invitation',compact('token','meeting','queue'))->header('Cache-Control','no-store')->header('Referrer-Policy','no-referrer')->header('X-Robots-Tag','noindex, nofollow');
    }

    public function join(Request $request, $id)
    {
        $ticket = $this->own($request,$id);
        return $this->joinTicket($ticket);
    }
    public function invitationJoin($token) { return $this->joinTicket($this->invitation($token)); }

    private function joinTicket(GuestTicket $ticket)
    {
        $visible = $ticket->meeting; abort_unless($visible,404);
        return DB::transaction(function () use ($ticket,$visible) {
            WorkUnit::where('name',$visible->work_unit)->lockForUpdate()->firstOrFail();
            $meeting = Meeting::whereKey($visible->id)->lockForUpdate()->firstOrFail();
            $ticket->setRelation('meeting',$meeting);
            abort_unless($ticket->inviteActive() && $meeting->called_at && $meeting->hasJoinUrl() && app(ConsultationQueueService::class)->state($meeting)['ready'],422,'Tunggu panggilan petugas dan giliran antrean Anda.');
            return redirect()->away($meeting->join_url)->header('Referrer-Policy','no-referrer');
        });
    }

    public function cancel(Request $request,$id)
    {
        $ticket = $this->own($request,$id);
        DB::transaction(function () use ($ticket) {
            if ($ticket->meeting_id) {
                WorkUnit::where('name',$ticket->meeting->work_unit)->lockForUpdate()->firstOrFail();
                $m = Meeting::whereKey($ticket->meeting_id)->lockForUpdate()->firstOrFail();
                abort_unless(in_array($m->status,['scheduled','started']),422);
                $m->update(['status'=>'cancelled']);
            } else { $ticket->room->update(['status'=>'closed']); }
        });
        return redirect()->route('public.ticket',$id)->with('success','Konsultasi dibatalkan.');
    }
}
