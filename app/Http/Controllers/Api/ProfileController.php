<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Mengupdate foto profil pengguna yang sedang login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = $request->user();

        // Hapus foto lama jika ada
        if ($user->profile_photo) {
            // Path lama sudah benar, tidak perlu diubah
            Storage::delete($user->profile_photo);
        }
    
        // Simpan foto baru dan dapatkan path internalnya (misal: public/profile-photos/file.jpg)
        $path = $request->file('photo')->store('public/profile-photos');
    
        // --- PERUBAHAN DI SINI ---
        // Simpan path internal, BUKAN Storage::url($path)
        $user->profile_photo = $path; 
        $user->save();
    
        return response()->json([
            'message' => 'Foto profil berhasil diperbarui',
            'user' => $user->fresh() // Muat ulang data user agar accessor berjalan
        ]);
    }
    
    public function showPhoto(Request $request)
    {
        $user = $request->user();

        if (!$user->profile_photo || !Storage::exists($user->profile_photo)) {
            abort(404, 'Foto profil tidak ditemukan.');
        }

        return response()->file(Storage::path($user->profile_photo));
    }
}