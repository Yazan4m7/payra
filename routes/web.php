<?php
use App\Http\Controllers\CentralAuthController;
use App\Http\Controllers\OperatorController;
use Illuminate\Support\Facades\Route;

Route::domain(config('tenancy.central_domains.0'))->group(function () {
    Route::get('/', fn () => redirect()->route('operator.login'));
    Route::middleware('guest:central')->group(function () {
        Route::get('/operator/login', [CentralAuthController::class,'create'])->name('operator.login');
        Route::post('/operator/login', [CentralAuthController::class,'store'])->name('operator.login.store');
    });
    Route::middleware('auth:central')->group(function () {
        Route::get('/operator', [OperatorController::class,'index'])->name('operator.dashboard');
        Route::post('/operator/tenants', [OperatorController::class,'store'])->name('operator.tenants.store');
        Route::patch('/operator/tenants/{tenant}/subscription', [OperatorController::class,'updateSubscription'])->name('operator.tenants.subscription');
        Route::post('/operator/logout', [CentralAuthController::class,'destroy'])->name('operator.logout');
    });
});
