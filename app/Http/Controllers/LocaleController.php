<?php
namespace App\Http\Controllers;
use Illuminate\Http\RedirectResponse;
class LocaleController extends Controller
{
    public function __invoke(string $locale): RedirectResponse
    {
        abort_unless(in_array($locale,['ar','en'],true),404); $user=request()->user(); if($user){$user->update(['locale'=>$locale]);}else{session(['locale'=>$locale]);} return back();
    }
}
