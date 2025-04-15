<?php

use Illuminate\Support\Facades\Route;
// Import Controller Frontend Anda
use App\Http\Controllers\ReportController;
use Spatie\Permission\Middleware\RoleMiddleware;
use App\Http\Controllers\Frontend\ContactController;
use App\Http\Controllers\Frontend\KelompokController;
use App\Http\Controllers\Frontend\AnalyticsController;
use App\Http\Controllers\Frontend\KemitraanController;
use App\Http\Controllers\Frontend\PendaftaranKelompokController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route Halaman Utama (Bisa diarahkan ke peta potensi atau halaman lain)
Route::get('/', [KelompokController::class, 'petaPotensi'])->name('home');

// Route Halaman Peta Potensi (dengan filter opsional)
Route::get('/peta-potensi', [KelompokController::class, 'petaPotensi'])->name('peta.potensi');

// Route Halaman Detail Kelompok (menggunakan Route Model Binding)
Route::get('/kelompok/{kelompok}', [KelompokController::class, 'show'])->name('kelompok.detail');

// Route Halaman Daftar Semua Kelompok (dengan pagination & filter opsional)
Route::get('/kelompok', [KelompokController::class, 'list'])->name('kelompok.list');

// Route untuk Laporan PDF (Gunakan middleware yang benar)
Route::get('/report/kelompok/{kelompok}/pdf', [ReportController::class, 'generateKelompokPdf'])
    ->name('report.kelompok.pdf')
    // Gunakan middleware 'auth' standar Laravel atau middleware guard Filament
    // ->middleware(['auth']); // Middleware 'auth' standar
    // ATAU jika ingin menggunakan middleware Filament Panel 'admin':
    ->middleware(\Filament\Http\Middleware\Authenticate::class . ':admin'); // Contoh middleware guard 'admin' Filament

// --- Route Halaman Analitik Admin ---
Route::get('/analitik-admin', [AnalyticsController::class, 'index'])
    ->middleware(['auth', RoleMiddleware::class . ':Admin']) // Terapkan middleware
    ->name('analytics.admin');
// ------------------------------------

// --- Route untuk Pendaftaran Kelompok ---
Route::get('/daftar-kelompok', [PendaftaranKelompokController::class, 'create'])->name('pendaftaran.kelompok.create');
Route::post('/daftar-kelompok/kirim', [PendaftaranKelompokController::class, 'store'])->name('pendaftaran.kelompok.store');
// ---------------------------------------

// --- Route untuk Cek Status Pendaftaran ---
Route::get('/cek-status-pendaftaran', [PendaftaranKelompokController::class, 'checkStatus'])->name('pendaftaran.kelompok.check-status');
Route::post('/cek-status-pendaftaran', [PendaftaranKelompokController::class, 'showStatus'])->name('pendaftaran.kelompok.show-status');
// ---------------------------------------

Route::post('/kontak/kirim', [ContactController::class, 'store'])->name('contact.store');

Route::post('/kemitraan/kirim', [KemitraanController::class, 'store'])->name('kemitraan.store');
