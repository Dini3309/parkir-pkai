<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CekRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $userRole = session('user_role');

        if (!$userRole) {
            return redirect()->route('login');
        }

        // Pisahkan role yang dikirim melalui middleware
        $rolesDiizinkan = [];

        foreach ($roles as $role) {
            $rolesDiizinkan = array_merge(
                $rolesDiizinkan,
                explode(',', $role)
            );
        }

        $rolesDiizinkan = array_map('trim', $rolesDiizinkan);

        if (!in_array($userRole, $rolesDiizinkan, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
