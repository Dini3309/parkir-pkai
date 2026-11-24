<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KendaraanController extends Controller
{
    // Menampilkan data kendaraan
    public function index()
    {
        $kendaraan = Kendaraan::all();

        $totalKendaraan = Kendaraan::count();

        $totalMobil = Kendaraan::whereRaw(
            'LOWER(TRIM(jenis_kendaraan)) = ?',
            ['mobil']
        )->count();

        $totalMotor = Kendaraan::whereRaw(
            'LOWER(TRIM(jenis_kendaraan)) = ?',
            ['motor']
        )->count();

        $totalLainnya = $totalKendaraan - $totalMobil - $totalMotor;

        return view('kendaraan.index', compact(
            'kendaraan',
            'totalKendaraan',
            'totalMobil',
            'totalMotor',
            'totalLainnya'
        ));
    }

    // Form tambah kendaraan
    public function create()
    {
        return view('kendaraan.create');
    }

    // Menyimpan kendaraan sekaligus membuat transaksi otomatis
    public function store(Request $request)
    {
        $request->validate([
            'no_plat' => 'required|string|max:15',
            'jenis_kendaraan' => 'required|string|max:100',
            'merk' => 'nullable|string|max:100',
            'nama_pemilik' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($request) {

            $idUserAktif = session('user_id');

            // Menyimpan data kendaraan
            $kendaraan = Kendaraan::create([
                'plat_nomer' => $request->no_plat,
                'jenis_kendaraan' => $request->jenis_kendaraan,
                'warna_kendaraan' => null,
                'pemilik' => $request->nama_pemilik,
                'id_user' => $idUserAktif,
            ]);

            // Membuat transaksi parkir secara otomatis
            DB::table('tb_transaksi')->insert([
                'id_kendaraan' => $kendaraan->id_kendaraan,
                'waktu_masuk' => now(),
                'waktu_keluar' => null,
                'id_tarif' => null,
                'biaya_total' => null,
                'status' => 'masuk',
                'id_user' => $idUserAktif,
                'id_area' => null,
            ]);
        });

        return redirect()
            ->route('kendaraan.index')
            ->with(
                'sukses',
                'Kendaraan berhasil ditambahkan dan otomatis masuk ke transaksi.'
            );
    }

    // Form edit kendaraan
    public function edit($id)
    {
        $kendaraan = Kendaraan::findOrFail($id);

        return view('kendaraan.edit', compact('kendaraan'));
    }

    // Memperbarui data kendaraan
    public function update(Request $request, $id)
    {
        $kendaraan = Kendaraan::findOrFail($id);

        $request->validate([
            'no_plat' => 'required|string|max:15',
            'jenis_kendaraan' => 'required|string|max:100',
            'merk' => 'nullable|string|max:100',
            'nama_pemilik' => 'nullable|string|max:100',
        ]);

        $kendaraan->update([
            'plat_nomer' => $request->no_plat,
            'jenis_kendaraan' => $request->jenis_kendaraan,
            'pemilik' => $request->nama_pemilik,
        ]);

        return redirect()
            ->route('kendaraan.index')
            ->with('sukses', 'Data kendaraan berhasil diperbarui.');
    }

    // Menghapus kendaraan
    public function destroy($id)
    {
        $kendaraan = Kendaraan::findOrFail($id);

        $kendaraan->delete();

        return redirect()
            ->route('kendaraan.index')
            ->with('sukses', 'Data kendaraan berhasil dihapus.');
    }
}
