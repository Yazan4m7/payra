<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth;
class CentralAuthController extends Controller
{
    public function create(){return view('operator.login');}
    public function store(Request $r){$c=$r->validate(['email'=>['required','email'],'password'=>['required']]);if(!Auth::guard('central')->attempt($c,$r->boolean('remember')))return back()->withErrors(['email'=>'Invalid credentials.']);$r->session()->regenerate();return redirect()->route('operator.dashboard');}
    public function destroy(Request $r){Auth::guard('central')->logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect()->route('operator.login');}
}
