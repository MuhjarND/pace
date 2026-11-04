<?php
namespace App\Http\Controllers;
use App\WhatsappNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FonnteWebhookController extends Controller
{
    public function status(Request $request,$secret)
    {
        $expected = (string)config('services.fonnte.webhook_secret');
        abort_unless(strlen($expected)>=32 && hash_equals($expected,$secret),404);
        foreach (['id','stateid','state','status'] as $key) {
            if (is_scalar($request->input($key))) $request->merge([$key=>(string)$request->input($key)]);
        }
        $data = $request->validate(['id'=>'nullable|string|max:100','stateid'=>'nullable|string|max:150',
            'status'=>'nullable|string|max:50','state'=>'nullable|string|max:50']);
        if (empty($data['id']) && empty($data['stateid'])) return response()->json(['ok'=>true]);
        app(\App\Services\WhatsappStatusService::class)->receive($data);
        return response()->json(['ok'=>true]);
    }
}
