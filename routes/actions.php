<?php

use Illuminate\Support\Facades\Route;
use QuebecStudioMods\ConsentKit\Statamic\Http\Controllers\RecordController;

/*
 * Statamic prefixes this with the action route and the addon slug, so the
 * endpoint is `/!/statamic-consent-kit/record`.
 *
 * No CSRF token: it would live in the HTML, which the banner keeps identical
 * for every visitor so pages stay cacheable — the property the register exists
 * to attest. `sendBeacon` sends none either. The controller's own guards stand
 * in for it. Named the way Statamic exempts its own routes, across the
 * middleware names Laravel has used.
 */
Route::post('record', RecordController::class)
    ->withoutMiddleware([
        'App\Http\Middleware\VerifyCsrfToken',
        'Illuminate\Foundation\Http\Middleware\VerifyCsrfToken',
        'Illuminate\Foundation\Http\Middleware\ValidateCsrfToken',
        'Illuminate\Foundation\Http\Middleware\PreventRequestForgery',
    ])
    ->name('cookie-consent-kit.record');
