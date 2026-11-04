<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    // Phone numbers are notification destinations, never authentication.
    public function index() { return redirect()->route('public.consult'); }
    public function registerForm() { return redirect()->route('public.consult'); }
    public function check(Request $request) { return redirect()->route('public.consult')->with('success', 'Mulai konsultasi baru sebagai tamu. Riwayat akun hanya tersedia setelah login dengan kata sandi.'); }
    public function register(Request $request) { return $this->check($request); }
}
