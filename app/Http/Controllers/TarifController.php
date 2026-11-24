<?php

namespace App\Http\Controllers;

use App\Models\Tarif;
use Illuminate\Http\Request;

class TarifController extends Controller
{
    public function index()
    {
        // Mengambil seluruh data tarif
        $tarif = Tarif::all();

        // Total seluruh tarif
        $totalTarif = $tarif->count();

        // Menghitung tarif motor
        $totalMotor = $tarif->filter(function ($item) {
            return strtolower(trim($item->jenis_kendaraan ?? '')) === 'motor';
        })->count();

        // Menghitung tarif mobil
        $totalMobil = $tarif->filter(function ($item) {
            return strtolower(trim($item->jenis_kendaraan ?? '')) === 'mobil';
        })->count();

        // Menghitung tarif selain motor dan mobil
        $totalLainnya = $totalTarif - $totalMotor - $totalMobil;

        return view('tarif.index', compact(
            'tarif',
            'totalTarif',
            'totalMotor',
            'totalMobil',
            'totalLainnya'
        ));
    }

    public function create()
    {
        return view('tarif.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'jenis_kendaraan' => 'required|string|max:50',
            'tarif_per_jam' => 'required|numeric|min:0',
        ]);

        Tarif::create($request->only([
            'jenis_kendaraan',
            'tarif_per_jam'
        ]));

        return redirect()->route('tarif.index')
            ->with('sukses', 'Tarif berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $tarif = Tarif::findOrFail($id);

        return view('tarif.edit', compact('tarif'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'jenis_kendaraan' => 'required|string|max:50',
            'tarif_per_jam' => 'required|numeric|min:0',
        ]);

        $tarif = Tarif::findOrFail($id);

        $tarif->update($request->only([
            'jenis_kendaraan',
            'tarif_per_jam'
        ]));

        return redirect()->route('tarif.index')
            ->with('sukses', 'Tarif berhasil diperbarui.');
    }

    public function destroy($id)
    {
        Tarif::findOrFail($id)->delete();

        return redirect()->route('tarif.index')
            ->with('sukses', 'Tarif berhasil dihapus.');
    }
}
