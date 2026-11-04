<?php

namespace App\Http\Controllers;

use App\AdminActivityLog;
use App\User;
use App\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminWorkUnitController extends Controller
{
    public function __construct() { $this->middleware('auth'); }
    private function authorizeAdmin() { abort_unless(auth()->user()->canManageMasterData(), 403); }

    public function index()
    {
        $this->authorizeAdmin();
        $workUnits = WorkUnit::orderBy('id')->paginate(15);
        $staffCounts = User::where('is_active', true)->whereIn('role', ['admin', 'operator'])->select('work_unit', DB::raw('COUNT(*) AS total'))->groupBy('work_unit')->pluck('total', 'work_unit');
        return view('admin.work-units.index', compact('workUnits', 'staffCounts'));
    }

    public function create()
    {
        $this->authorizeAdmin();
        return view('admin.work-units.form', ['workUnit' => new WorkUnit(['is_active' => true])]);
    }

    public function edit($id)
    {
        $this->authorizeAdmin();
        return view('admin.work-units.form', ['workUnit' => WorkUnit::findOrFail($id)]);
    }

    public function store(Request $request) { return $this->save($request); }
    public function update(Request $request, $id) { return $this->save($request, $id); }

    private function save(Request $request, $id = null)
    {
        $this->authorizeAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('work_units')->ignore($id)],
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('work_units')->ignore($id)],
            'website' => 'nullable|url|max:500|regex:~\Ahttps://~', 'public_requirements' => 'nullable|string|max:3000',
            'is_active' => 'required|boolean', 'address' => 'nullable|string|max:500', 'phone' => ['nullable', 'regex:/^[0-9+() .-]{6,30}$/'],
        ]);
        $unit = DB::transaction(function () use ($id, $data) {
            $unit = $id ? WorkUnit::whereKey($id)->lockForUpdate()->firstOrFail() : new WorkUnit;
            // The existing application stores the unit name on historical records.
            if ($id && $unit->name !== $data['name']) {
                throw ValidationException::withMessages(['name' => 'Nama satker merupakan identitas tetap agar riwayat pelayanan tetap terhubung.']);
            }
            $before = $id ? $unit->only(['code', 'is_active', 'address', 'phone']) : null;
            $unit->fill($data)->save();
            AdminActivityLog::create(['actor_id' => auth()->id(), 'action' => $id ? 'work_unit.updated' : 'work_unit.created', 'target_type' => 'work_unit', 'target_id' => $unit->id, 'changes' => ['before' => $before, 'after' => $unit->only(['code', 'is_active', 'address', 'phone'])]]);
            return $unit;
        });
        return redirect()->route('admin.work-units.edit', $unit)->with('success', 'Satuan kerja disimpan. Daftar satker langsung tersedia pada pendaftaran dan penempatan akun.');
    }
}
