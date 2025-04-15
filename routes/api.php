<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\PendaftaranKelompokController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PetugasLapanganController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// --- Route API untuk mengambil Desa berdasarkan Kecamatan ---
Route::get('/desa-by-kecamatan/{kecamatan}', [PendaftaranKelompokController::class, 'getDesaByKecamatan'])
        ->where('kecamatan', '[0-9]+') // Pastikan parameter adalah angka
        ->name('api.desa.by.kecamatan');
// ---------------------------------------------------------

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::get('/desa-by-kecamatan/{kecamatan}', function ($kecamatan) {
    return \App\Models\Desa::where('kecamatan_id', $kecamatan)->get();
});

// Authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Petugas Lapangan routes
    Route::prefix('petugas-lapangan')->middleware('role:Petugas Lapangan')->group(function () {
        // Kelompok routes
        Route::get('/kelompok', [PetugasLapanganController::class, 'kelompok']);
        Route::get('/kelompok/{id}', [PetugasLapanganController::class, 'kelompokDetail']);
        
        // Distribusi Bantuan routes
        Route::get('/kelompok/{kelompokId}/distribusi-bantuan', [PetugasLapanganController::class, 'distribusiBantuan']);
        Route::post('/kelompok/{kelompokId}/distribusi-bantuan', [PetugasLapanganController::class, 'storeDistribusiBantuan']);
        
        // Monitoring Pemanfaatan routes
        Route::get('/kelompok/{kelompokId}/monitoring-pemanfaatan', [PetugasLapanganController::class, 'monitoringPemanfaatan']);
        Route::post('/kelompok/{kelompokId}/monitoring-pemanfaatan', [PetugasLapanganController::class, 'storeMonitoringPemanfaatan']);
        
        // Pelaporan Hasil routes
        Route::get('/kelompok/{kelompokId}/pelaporan-hasil', [PetugasLapanganController::class, 'pelaporanHasil']);
        Route::post('/kelompok/{kelompokId}/pelaporan-hasil', [PetugasLapanganController::class, 'storePelaporanHasil']);
    });
});
