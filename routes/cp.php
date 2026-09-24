<?php

use Illuminate\Support\Facades\Route;
use QuebecStudioMods\ConsentKit\Statamic\Http\Controllers\RegistryController;

/*
 * Reading the register is a permission of its own, not an admin matter: the
 * point is to hand someone a proof without opening the settings to them.
 */
Route::prefix('cookie-consent-kit/registry')
    ->name('cookie-consent-kit.registry.')
    ->middleware('can:view consent register')
    ->group(function (): void {
        Route::get('/', [RegistryController::class, 'index'])->name('index');
        // What the listing component fetches as it filters, sorts and pages.
        Route::get('data', [RegistryController::class, 'data'])->name('data');
        Route::get('export', [RegistryController::class, 'export'])->name('export')->middleware('can:export consent register');
        Route::get('{id}', [RegistryController::class, 'show'])->name('show');
    });
