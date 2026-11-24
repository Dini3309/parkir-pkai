<?php

namespace App\Http\Controllers;

use App\Models\AreaParkir;
use App\Models\Parkir;
use Illuminate\Http\Request;

class AreaParkirController extends Controller
{
    public function index()
    {
        $areaParkir = AreaParkir::all();

        // Total seluruh area parkir
        $totalArea = AreaParkir::count();

        // Karena tabel tidak memiliki kolom status,
        // semua area yang terdaftar dihitung sebagai aktif
        $totalAktif = $totalArea;

        // Total kapasitas seluruh area
        $totalKapasitas = AreaParkir::sum('kapasitas');

        // Jumlah kendaraan yang masih berada di area parkir
        $totalTerisi = Parkir::where('status', 'masuk')
            ->count();

        return view('area.index', compact(
            'areaParkir',
            'totalArea',
            'totalAktif',
            'totalKapasitas',
            'totalTerisi'
        ));
    }

    public function create()
    {
        return view('area.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_area' => 'required|string|max:50',
            'kapasitas' => 'required|numeric|min:1',
        ]);

        AreaParkir::create($request->only([
            'nama_area',
            'kapasitas'
        ]));

        return redirect()->route('area.index')
            ->with('sukses', 'Area parkir berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $area = AreaParkir::findOrFail($id);

        return view('area.edit', compact('area'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_area' => 'required|string|max:50',
            'kapasitas' => 'required|numeric|min:1',
        ]);

        $area = AreaParkir::findOrFail($id);

        $area->update($request->only([
            'nama_area',
            'kapasitas'
        ]));

        return redirect()->route('area.index')
            ->with('sukses', 'Data area berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $area = AreaParkir::findOrFail($id);
        $area->delete();

        return redirect()->route('area.index')
            ->with('sukses', 'Area parkir berhasil dihapus.');
    }
}
