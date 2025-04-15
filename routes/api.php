<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\PendaftaranKelompokController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// --- Route API untuk mengambil Desa berdasarkan Kecamatan ---
Route::get('/desa-by-kecamatan/{kecamatan}', [PendaftaranKelompokController::class, 'getDesaByKecamatan'])
        ->where('kecamatan', '[0-9]+') // Pastikan parameter adalah angka
        ->name('api.desa.by.kecamatan');
// ---------------------------------------------------------
