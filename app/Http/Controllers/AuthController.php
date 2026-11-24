<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    // Menampilkan halaman login
    public function showLogin()
    {
        return view('login');
    }

    // Proses login
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
            'role' => 'required',
        ]);

        // Membersihkan spasi username
        $username = trim($request->username);

        // Mencari akun berdasarkan username
        $user = DB::table('tb_user')
            ->whereRaw('LOWER(TRIM(username)) = ?', [
                strtolower($username)
            ])
            ->first();

        // Jika username tidak ditemukan
        if (!$user) {
            return back()
                ->withErrors([
                    'login' => 'Username tidak ditemukan.'
                ])
                ->withInput($request->only('username', 'role'));
        }

        // Memeriksa password
        $passwordDatabase = (string) $user->password;
        $passwordInput = (string) $request->password;

        if (
            str_starts_with($passwordDatabase, '$2y$') ||
            str_starts_with($passwordDatabase, '$argon2')
        ) {
            $passwordBenar = Hash::check(
                $passwordInput,
                $passwordDatabase
            );
        } else {
            $passwordBenar = hash_equals(
                $passwordDatabase,
                $passwordInput
            );
        }

        if (!$passwordBenar) {
            return back()
                ->withErrors([
                    'login' => 'Password salah.'
                ])
                ->withInput($request->only('username', 'role'));
        }

        // Memeriksa role
        $roleDatabase = strtolower(trim($user->role));
        $roleInput = strtolower(trim($request->role));

        if ($roleDatabase !== $roleInput) {
            return back()
                ->withErrors([
                    'login' => 'Role tidak sesuai dengan akun.'
                ])
                ->withInput($request->only('username', 'role'));
        }

        // Memeriksa status akun
        if (
            isset($user->status_aktif) &&
            $user->status_aktif != 1
        ) {
            return back()
                ->withErrors([
                    'login' => 'Akun sedang tidak aktif.'
                ])
                ->withInput($request->only('username', 'role'));
        }

        // Menyimpan session
        Session::put('user_id', $user->id_user);
        Session::put('username', $user->username);
        Session::put('user_role', $roleDatabase);

        Session::regenerate();

        // Mengarahkan sesuai role
        if ($roleDatabase === 'admin') {
            return redirect()
                ->route('dashboard')
                ->with('success', 'Login sebagai Admin berhasil.');
        }

        if ($roleDatabase === 'petugas') {
            return redirect()
                ->route('petugas.dashboard')
                ->with('success', 'Login sebagai Petugas berhasil.');
        }

        if ($roleDatabase === 'owner') {
            return redirect()
                ->route('owner.dashboard')
                ->with('success', 'Login sebagai Owner berhasil.');
        }

        Session::flush();

        return back()
            ->withErrors([
                'login' => 'Role akun tidak dikenali.'
            ]);
    }

    // Logout
    public function logout()
    {
        Session::flush();

        return redirect()
            ->route('login')
            ->with('success', 'Berhasil logout.');
    }
}
