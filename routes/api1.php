<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JurnalController;
use App\Http\Controllers\Api\ProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Di sini Anda mendaftarkan rute API untuk aplikasi Anda. Rute-rute ini
| dimuat oleh RouteServiceProvider dan secara otomatis diberi prefix '/api'.
|
*/

// =========================================================================
// RUTE PUBLIK - Tidak Perlu Login
// =========================================================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


// =========================================================================
// RUTE TERPROTEKSI - Wajib Login (Menggunakan Sanctum)
// =========================================================================
Route::middleware('auth:sanctum')->group(function () {

    // --- Auth & User ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        // Mengambil data pengguna yang sedang login
        return $request->user();
    });

    // --- Profile ---
    Route::post('/user/profile-photo', [ProfileController::class, 'updatePhoto']);
    Route::get('/user/profile-photo', [ProfileController::class, 'showPhoto']);

    // --- Jurnal (CRUD Lengkap) ---
    // Menggunakan rute individual untuk kontrol penuh
    Route::get('/jurnals', [JurnalController::class, 'index']);       // GET:    Ambil semua jurnal (dengan filter)
    Route::post('/jurnals', [JurnalController::class, 'store']);      // POST:   Simpan jurnal baru
    Route::get('/jurnals/{jurnal}', [JurnalController::class, 'show']); // GET:    Ambil satu jurnal spesifik
    Route::put('/jurnals/{jurnal}', [JurnalController::class, 'update']); // PUT:    Update jurnal yang sudah ada
    Route::delete('/jurnals/{jurnal}', [JurnalController::class, 'destroy']); // DELETE: Hapus jurnal

    // Rute khusus untuk mengambil foto dengan pengecekan hak akses
    Route::get('/jurnals/{jurnal}/foto', [JurnalController::class, 'showPhoto']);
});