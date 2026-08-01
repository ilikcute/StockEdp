<?php

use Illuminate\Support\Facades\Route;

// Semua request web (bukan API) diarahkan ke shell Vue SPA.
// Vue Router menangani navigasi selanjutnya di sisi client.
// Pattern dikecualikan dari prefix 'api' agar tidak menangkap request API yang gagal.
Route::get('/{any?}', fn () => view('app'))
    ->where('any', '^(?!api/).*');
