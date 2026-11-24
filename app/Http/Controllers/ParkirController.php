<?php

namespace App\Http\Controllers;

use App\Models\Parkir;
use App\Models\Tarif;
use App\Models\Kendaraan;
use App\Models\AreaParkir;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ParkirController extends Controller
{
    // Menghitung biaya parkir berdasarkan jam.
    // Setiap kelebihan waktu dibulatkan ke jam berikutnya.
    private function hitungBiaya($waktuMasuk, $waktuAkhir, $tarifPerJam)
    {
        $detik = max(
            0,
            Carbon::parse($waktuAkhir)->getTimestamp()
            - Carbon::parse($waktuMasuk)->getTimestamp()
        );

        $jumlahJam = max(1, (int) ceil($detik / 3600));

        return (int) ($jumlahJam * (float) $tarifPerJam);
    }

    // Menampilkan semua transaksi.
    public function index()
    {
        $transaksi = Parkir::with([
            'tarif',
            'kendaraan',
            'petugas',
            'area'
        ])
            ->orderBy('id_parkir', 'desc')
            ->get();

        $sekarang = now();

        $transaksi->each(function ($item) use ($sekarang) {
            if (!$item->waktu_masuk) {
                $item->durasi_tampil = '-';
                $item->biaya_tampil = null;
                return;
            }

            $waktuMasuk = Carbon::parse($item->waktu_masuk);

            $sudahKeluar = $item->status === 'keluar'
                || !empty($item->waktu_keluar);

            $waktuAkhir = $sudahKeluar
                ? Carbon::parse($item->waktu_keluar)
                : $sekarang;

            // Menghitung durasi.
            $totalMenit = max(
                0,
                (int) floor(
                    ($waktuAkhir->getTimestamp()
                    - $waktuMasuk->getTimestamp()) / 60
                )
            );

            $jam = intdiv($totalMenit, 60);
            $menit = $totalMenit % 60;

            $bagianDurasi = [];

            if ($jam > 0) {
                $bagianDurasi[] = $jam . ' jam';
            }

            if ($menit > 0) {
                $bagianDurasi[] = $menit . ' menit';
            }

            if (empty($bagianDurasi)) {
                $bagianDurasi[] = '0 menit';
            }

            $item->durasi_tampil = implode(' ', $bagianDurasi);

            // Menghitung biaya berdasarkan jam.
            $tarifPerJam = (float) ($item->tarif->tarif_per_jam ?? 0);

            if ($tarifPerJam > 0) {
                $item->biaya_tampil = $this->hitungBiaya(
                    $waktuMasuk,
                    $waktuAkhir,
                    $tarifPerJam
                );
            } else {
                $item->biaya_tampil = null;
            }
        });

        $totalTransaksi = Parkir::count();

        $totalMasuk = Parkir::where('status', 'masuk')->count();

        $totalKeluar = Parkir::where('status', 'keluar')->count();

        // Pendapatan hanya dari kendaraan yang sudah keluar.
        $totalPendapatan = $transaksi
            ->where('status', 'keluar')
            ->sum('biaya_tampil');

        return view('transaksi.index', compact(
            'transaksi',
            'totalTransaksi',
            'totalMasuk',
            'totalKeluar',
            'totalPendapatan'
        ));
    }

    // Form transaksi baru.
    public function create()
    {
        $kendaraan = Kendaraan::all();
        $tarif = Tarif::all();
        $area = AreaParkir::all();

        return view('parkir.create', compact(
            'kendaraan',
            'tarif',
            'area'
        ));
    }

    // Menyimpan transaksi kendaraan masuk.
    public function store(Request $request)
    {
        $request->validate([
            'id_kendaraan' => 'required|exists:tb_kendaraan,id_kendaraan',
            'id_tarif' => 'required|exists:tb_id_tarif,id_tarif',
            'id_area' => 'required|exists:tb_area_parkir,id_area',
        ]);

        Parkir::create([
            'id_kendaraan' => $request->id_kendaraan,
            'id_tarif' => $request->id_tarif,
            'id_area' => $request->id_area,
            'id_user' => session('user_id'),
            'waktu_masuk' => now(),
            'waktu_keluar' => null,
            'biaya_total' => null,
            'status' => 'masuk',
        ]);

        return redirect()
            ->route('parkir.index')
            ->with('sukses', 'Transaksi kendaraan masuk berhasil ditambahkan.');
    }

    // Form edit transaksi.
    public function edit($id)
    {
        $parkir = Parkir::findOrFail($id);

        $kendaraan = Kendaraan::all();
        $tarif = Tarif::all();
        $area = AreaParkir::all();

        return view('parkir.edit', compact(
            'parkir',
            'kendaraan',
            'tarif',
            'area'
        ));
    }

    // Update data transaksi.
    public function update(Request $request, $id)
    {
        $request->validate([
            'id_kendaraan' => 'required|exists:tb_kendaraan,id_kendaraan',
            'id_tarif' => 'required|exists:tb_id_tarif,id_tarif',
            'id_area' => 'required|exists:tb_area_parkir,id_area',
        ]);

        $parkir = Parkir::findOrFail($id);

        $parkir->update([
            'id_kendaraan' => $request->id_kendaraan,
            'id_tarif' => $request->id_tarif,
            'id_area' => $request->id_area,
        ]);

        return redirect()
            ->route('parkir.index')
            ->with('sukses', 'Transaksi berhasil diperbarui.');
    }

    // Proses kendaraan keluar khusus Petugas.
    public function keluar($id)
    {
        $hasil = DB::transaction(function () use ($id) {
            $parkir = Parkir::with('tarif')
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $parkir->status === 'keluar'
                || $parkir->waktu_keluar !== null
            ) {
                return [
                    false,
                    'Kendaraan ini sudah tercatat keluar.'
                ];
            }

            if (!$parkir->waktu_masuk) {
                return [
                    false,
                    'Waktu masuk kendaraan belum tersedia.'
                ];
            }

            if (
                !$parkir->tarif
                || $parkir->tarif->tarif_per_jam === null
            ) {
                return [
                    false,
                    'Tarif parkir kendaraan belum tersedia.'
                ];
            }

            $waktuKeluar = now();

            $tarifPerJam = (float) $parkir->tarif->tarif_per_jam;

            $biaya = $this->hitungBiaya(
                $parkir->waktu_masuk,
                $waktuKeluar,
                $tarifPerJam
            );

            $parkir->update([
                'waktu_keluar' => $waktuKeluar,
                'biaya_total' => $biaya,
                'status' => 'keluar',
            ]);

            return [
                true,
                'Kendaraan berhasil keluar. Biaya parkir: Rp '
                . number_format($biaya, 0, ',', '.')
            ];
        });

        return redirect()
            ->route('parkir.index')
            ->with(
                $hasil[0] ? 'sukses' : 'error',
                $hasil[1]
            );
    }

    // Hapus transaksi.
    public function destroy($id)
    {
        $parkir = Parkir::findOrFail($id);

        $parkir->delete();

        return redirect()
            ->route('parkir.index')
            ->with('sukses', 'Transaksi berhasil dihapus.');
    }

    // Cetak struk.
    public function struk($id)
    {
        $parkir = Parkir::with([
            'tarif',
            'kendaraan',
            'petugas',
            'area'
        ])->findOrFail($id);

        return view('parkir.struk', compact('parkir'));
    }
}