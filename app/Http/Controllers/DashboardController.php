<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // DASHBOARD ADMIN
    public function index()
    {
        $totalUser = DB::table('tb_user')->count();

        $totalKendaraan = DB::table('tb_kendaraan')->count();

        $totalArea = DB::table('tb_area_parkir')->count();

        $totalTarif = DB::table('tb_id_tarif')->count();

        return view('admin.dashboard', compact(
            'totalUser',
            'totalKendaraan',
            'totalArea',
            'totalTarif'
        ));
    }

    // DASHBOARD PETUGAS
    public function petugas()
    {
        return view('petugas.dashboard');
    }

    // DASHBOARD OWNER
    public function owner()
    {
        return view('owner.dashboard');
    }
}
