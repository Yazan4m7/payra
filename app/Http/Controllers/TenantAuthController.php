<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantAuthController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(array_merge($credentials, ['active' => 1]), $request->boolean('remember'))) {
            return back()->withErrors(['email' => __('auth.failed')])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();
        $destination = $user->isHr() ? route('dashboard') : route('self-service');

        return redirect()->intended($destination);
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
