<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DoctorWebAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (auth()->user()->role !== 'doctor' || auth()->user()->status !== 'active') {
            abort(403, 'Acces reserve aux medecins.');
        }

        return $next($request);
    }
}
