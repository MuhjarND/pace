<?php

namespace App\Http\Controllers;

use App\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $services = Service::where('is_active', true)->get();
        return view('services.index', compact('services'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canManageMasterData(), 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'nullable|string',
            'category' => 'required|string',
        ]);

        Service::create($request->only('name', 'description', 'icon', 'category'));
        return redirect()->route('services.index')->with('success', 'Layanan berhasil ditambahkan!');
    }
}
