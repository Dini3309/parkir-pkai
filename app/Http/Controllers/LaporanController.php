<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => [
                'nullable',
                'date',
                'after_or_equal:tanggal_mulai',
            ],
        ]);

        // REKAP KENDARAAN MASUK
        $queryMasuk = DB::table('tb_transaksi')
            ->whereNotNull('waktu_masuk');

        if ($request->filled('tanggal_mulai')) {
            $queryMasuk->where(
                'waktu_masuk',
                '>=',
                $request->tanggal_mulai . ' 00:00:00'
            );
        }

        if ($request->filled('tanggal_selesai')) {
            $queryMasuk->where(
                'waktu_masuk',
                '<',
                Carbon::parse($request->tanggal_selesai)
                    ->addDay()
                    ->startOfDay()
            );
        }

        $totalMasuk = $queryMasuk->count();

        // REKAP KENDARAAN KELUAR
        $query = DB::table('tb_transaksi')
            ->leftJoin(
                'tb_kendaraan',
                'tb_transaksi.id_kendaraan',
                '=',
                'tb_kendaraan.id_kendaraan'
            )
            ->leftJoin(
                'tb_area_parkir',
                'tb_transaksi.id_area',
                '=',
                'tb_area_parkir.id_area'
            )
            ->select(
                'tb_transaksi.*',
                'tb_kendaraan.plat_nomer',
                'tb_kendaraan.jenis_kendaraan',
                'tb_area_parkir.nama_area'
            )
            ->where('tb_transaksi.status', 'keluar')
            ->whereNotNull('tb_transaksi.waktu_keluar');

        if ($request->filled('tanggal_mulai')) {
            $query->where(
                'tb_transaksi.waktu_keluar',
                '>=',
                $request->tanggal_mulai . ' 00:00:00'
            );
        }

        if ($request->filled('tanggal_selesai')) {
            $query->where(
                'tb_transaksi.waktu_keluar',
                '<',
                Carbon::parse($request->tanggal_selesai)
                    ->addDay()
                    ->startOfDay()
            );
        }

        $transaksi = $query
            ->orderByDesc('tb_transaksi.waktu_keluar')
            ->get();

        $totalKeluar = $transaksi->count();
        $totalTransaksi = $totalKeluar;
        $totalPendapatan = $transaksi->sum('biaya_total');

        return view('laporan.index', compact(
            'transaksi',
            'totalTransaksi',
            'totalMasuk',
            'totalKeluar',
            'totalPendapatan'
        ));
    }
}
