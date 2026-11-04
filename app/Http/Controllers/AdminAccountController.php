<?php

namespace App\Http\Controllers;

use App\AdminActivityLog;
use App\ChatRoom;
use App\Meeting;
use App\User;
use App\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminAccountController extends Controller
{
    public function __construct() { $this->middleware('auth'); }
    private function authorizeAdmin() { abort_unless(auth()->user()->canManageMasterData(), 403); }

    public function index(Request $request)
    {
        $this->authorizeAdmin();
        $request->validate(['q' => 'nullable|string|max:80', 'role' => ['nullable', Rule::in(['admin', 'operator', 'user'])], 'status' => ['nullable', Rule::in(['active', 'inactive'])]]);
        $query = User::where('is_guest', false);
        if ($request->filled('q')) {
            $query->where(function ($query) use ($request) {
                $term = '%'.$request->q.'%';
                $query->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('work_unit', 'like', $term);
            });
        }
        if ($request->filled('role')) { $query->where('role', $request->role); }
        if ($request->filled('status')) { $query->where('is_active', $request->status === 'active'); }
        $accounts = $query->orderBy('name')->paginate(15)->appends($request->except('page'));
        return view('admin.accounts.index', compact('accounts'));
    }

    public function create()
    {
        $this->authorizeAdmin();
        return view('admin.accounts.form', ['account' => new User(['role' => 'operator']), 'workUnits' => WorkUnit::activeNames(), 'activity' => collect()]);
    }

    public function edit($id)
    {
        $this->authorizeAdmin();
        $account = User::where('is_guest', false)->findOrFail($id);
        abort_unless(!$account->hasComplaintAssignment() || auth()->user()->canManageComplaintAccess(),403);
        $workUnits = array_values(array_unique(array_merge(WorkUnit::activeNames(), array_filter([$account->work_unit]))));
        $activity = AdminActivityLog::with('actor')->where('target_type', 'account')->where('target_id', $account->id)->latest('id')->limit(5)->get();
        return view('admin.accounts.form', compact('account', 'workUnits', 'activity'));
    }

    public function store(Request $request) { return $this->save($request); }
    public function update(Request $request, $id) { return $this->save($request, $id); }

    private function save(Request $request, $id = null)
    {
        $this->authorizeAdmin();
        $existing = $id ? User::findOrFail($id) : null;
        $units = WorkUnit::activeNames();
        if ($existing && $existing->work_unit) { $units[] = $existing->work_unit; }
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'phone' => ['nullable', 'regex:/^[0-9]{10,15}$/', Rule::unique('users')->ignore($id)],
            'role' => ['required', Rule::in(['admin', 'operator', 'user'])],
            'work_unit' => ['required', Rule::in($units)],
            'is_active' => 'required|boolean', 'can_monitor' => 'required|boolean',
            'can_handle_complaints' => 'sometimes|boolean',
            'password' => ($id ? 'nullable' : 'required').'|string|min:10|max:72|confirmed',
        ]);
        if ($data['role'] === 'user' && $data['can_monitor']) {
            throw ValidationException::withMessages(['can_monitor' => 'Akses monitoring hanya untuk admin atau petugas.']);
        }

        $account = DB::transaction(function () use ($data, $id, $request) {
            // Serialize privilege changes so two requests cannot remove the last administrator.
            User::where('role', 'admin')->where('can_monitor', true)->orderBy('id')->lockForUpdate()->get();
            $account = $id ? User::where('is_guest',false)->whereKey($id)->lockForUpdate()->firstOrFail() : new User;
            $handler = array_key_exists('can_handle_complaints',$data) ? (bool)$data['can_handle_complaints'] : (bool)$account->can_handle_complaints;
            $targetPtaRoot = $data['role']==='admin' && $data['can_monitor'] && $data['work_unit']===config('ptsp.supervising_work_unit');
            // Protect passwords and placement too: an outside admin must not take over a confidential PTA account.
            abort_unless((!$account->hasComplaintAssignment() && !$handler && !$targetPtaRoot)
                || auth()->user()->canManageComplaintAccess(),403);
            if ($handler && (!in_array($data['role'],['admin','operator'],true) || $data['work_unit']!==config('ptsp.supervising_work_unit'))) {
                throw ValidationException::withMessages(['can_handle_complaints'=>'Petugas pengaduan harus admin/petugas pada PTA Papua Barat. Hapus penunjukan sebelum memindahkan akun.']);
            }
            if ($account->exists && $account->canManageComplaintAccess() && (!$targetPtaRoot || !$data['is_active'])
                && !User::where('work_unit',config('ptsp.supervising_work_unit'))->where('role','admin')->where('can_monitor',true)
                    ->where('is_active',true)->where('is_guest',false)->where('id','!=',$account->id)->exists()) {
                throw ValidationException::withMessages(['is_active'=>'Pertahankan sedikitnya satu admin pusat PTA aktif untuk mengelola pengaduan.']);
            }
            $before = $account->only(['role', 'work_unit', 'is_active', 'can_monitor', 'can_handle_complaints']);
            $losesManagement = $data['role'] !== 'admin' || !$data['can_monitor'] || !$data['is_active'];
            if ($account->exists && $account->id === auth()->id() && $losesManagement) {
                throw ValidationException::withMessages(['is_active' => 'Anda tidak dapat menghapus akses administrasi akun yang sedang digunakan.']);
            }
            if ($account->exists && $account->canManageMasterData() && $account->is_active && $losesManagement
                && !User::where('role', 'admin')->where('can_monitor', true)->where('is_active', true)->where('id', '!=', $account->id)->exists()) {
                throw ValidationException::withMessages(['is_active' => 'Setidaknya satu admin pusat harus tetap aktif.']);
            }

            $account->fill(collect($data)->only(['name', 'email', 'phone', 'role', 'work_unit', 'can_monitor'])->all());
            $account->is_active = (bool) $data['is_active'];
            $account->can_handle_complaints = $handler;
            $revoke = $account->exists && ($account->isDirty(['role', 'work_unit', 'is_active', 'can_monitor', 'can_handle_complaints']) || !empty($data['password']));
            if (!empty($data['password'])) { $account->password = Hash::make($data['password']); }
            if ($revoke) {
                $account->auth_version++;
                $account->remember_token = Str::random(60);
                $account->is_online = false;
            }
            $account->save();
            if ($revoke) {
                ChatRoom::where('admin_id', $account->id)->where('status', 'open')->update(['admin_id' => null]);
                Meeting::where('admin_id', $account->id)->whereIn('status', ['scheduled', 'started'])->update(['admin_id' => null]);
            }
            AdminActivityLog::create([
                'actor_id' => auth()->id(), 'action' => $id ? 'account.updated' : 'account.created',
                'target_type' => 'account', 'target_id' => $account->id,
                'changes' => ['before' => $id ? $before : null, 'after' => $account->only(['role', 'work_unit', 'is_active', 'can_monitor', 'can_handle_complaints']), 'password_changed' => !empty($data['password'])],
            ]);
            if ($account->id === auth()->id()) { $request->session()->put('auth_version', $account->auth_version); }
            return $account;
        });
        return redirect()->route('admin.accounts.edit', $account)->with('success', $id ? 'Akun diperbarui. Perubahan akses berlaku pada permintaan berikutnya.' : 'Akun berhasil dibuat. Sampaikan kredensial secara pribadi kepada pemilik akun.');
    }
}
