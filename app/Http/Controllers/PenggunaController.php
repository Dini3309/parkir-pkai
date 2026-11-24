<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PenggunaController extends Controller
{
    // MENAMPILKAN DATA PENGGUNA
    public function index()
    {
        $pengguna = DB::table('tb_user')
            ->select(
                'id_user',
                'nama_lengkap',
                'username',
                'role',
                'status_aktif'
            )
            ->orderBy('id_user', 'desc')
            ->get();

        $totalUser = DB::table('tb_user')->count();

        $totalAdmin = DB::table('tb_user')
            ->where('role', 'admin')
            ->count();

        $totalPetugas = DB::table('tb_user')
            ->where('role', 'petugas')
            ->count();

        $totalOwner = DB::table('tb_user')
            ->where('role', 'owner')
            ->count();

        return view('pengguna.index', compact(
            'pengguna',
            'totalUser',
            'totalAdmin',
            'totalPetugas',
            'totalOwner'
        ));
    }

    // HALAMAN TAMBAH PENGGUNA
    public function create()
    {
        return view('pengguna.create');
    }

    // SIMPAN PENGGUNA BARU
    public function store(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:tb_user,username',
            'password' => 'required|min:3',
            'role' => 'required|in:admin,petugas,owner',
            'status_aktif' => 'required|in:0,1',
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 3 karakter.',
            'role.required' => 'Silakan pilih role.',
            'role.in' => 'Role yang dipilih tidak valid.',
            'status_aktif.required' => 'Silakan pilih status.',
        ]);

        DB::table('tb_user')->insert([
            'nama_lengkap' => $request->nama_lengkap,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status_aktif' => $request->status_aktif,
        ]);

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    // HALAMAN EDIT PENGGUNA
    public function edit($id)
    {
        $pengguna = DB::table('tb_user')
            ->where('id_user', $id)
            ->first();

        if (!$pengguna) {
            return redirect()
                ->route('pengguna.index')
                ->with('error', 'Data pengguna tidak ditemukan.');
        }

        return view('pengguna.edit', compact('pengguna'));
    }

    // UPDATE DATA PENGGUNA
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:tb_user,username,' . $id . ',id_user',
            'role' => 'required|in:admin,petugas,owner',
            'status_aktif' => 'required|in:0,1',
            'password' => 'nullable|min:3',
        ]);

        $data = [
            'nama_lengkap' => $request->nama_lengkap,
            'username' => $request->username,
            'role' => $request->role,
            'status_aktif' => $request->status_aktif,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        DB::table('tb_user')
            ->where('id_user', $id)
            ->update($data);

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    // HAPUS PENGGUNA
    public function destroy($id)
    {
        DB::table('tb_user')
            ->where('id_user', $id)
            ->delete();

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }
}
