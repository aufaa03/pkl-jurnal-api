<?php

namespace App\Http\Controllers\Api;

use App\Models\Jurnal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class JurnalController extends Controller
{
    /**
     * Menampilkan daftar jurnal milik pengguna dengan opsi filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Mulai query builder untuk jurnal milik pengguna yang sedang login
        $query = $request->user()->jurnals();

        // Filter berdasarkan tanggal spesifik (format: YYYY-MM-DD)
         // Filter berdasarkan tanggal spesifik (format: YYYY-MM-DD)
    if ($request->has('tanggal')) {
        // BENAR: Ini hanya akan membandingkan bagian tanggalnya saja
        $query->whereDate('tanggal', $request->tanggal);
    }

        // Filter berdasarkan bulan dan tahun (format: YYYY-MM)
        if ($request->has('bulan')) {
            list($year, $month) = explode('-', $request->bulan);
            $query->whereYear('tanggal', $year)->whereMonth('tanggal', $month);
        }

        // Ambil data dan urutkan berdasarkan tanggal kegiatan (terbaru dulu)
        $jurnals = $query->orderBy('tanggal', 'desc')->get();

        return response()->json($jurnals, 200);
    }

    /**
     * Menyimpan jurnal baru ke database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'tanggal' => 'required|date_format:Y-m-d H:i',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);
        //dd($request->file('foto'));
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            // Simpan foto dan dapatkan path internalnya (misal: public/fotos/file.jpg)
            $path = $request->file('foto')->store('public/fotos');
            
            // --- PERUBAHAN DI SINI ---
            $fotoPath = $path; // Simpan path internal, BUKAN Storage::url($path)
        }
    
        $jurnal = $request->user()->jurnals()->create([
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'tanggal' => $request->tanggal,
            'foto' => $fotoPath
        ]);
    
        return response()->json($jurnal, 201);
    }

    /**
     * Menampilkan satu data jurnal spesifik.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Jurnal  $jurnal
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Jurnal $jurnal)
    {
        // Pastikan pengguna hanya bisa melihat jurnal miliknya sendiri
        if ($request->user()->id !== $jurnal->user_id) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }
        return response()->json($jurnal, 200);
    }

    /**
     * Memperbarui data jurnal yang sudah ada.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Jurnal  $jurnal
     * @return \Illuminate\Http\JsonResponse
     */
    // app/Http/Controllers/Api/JurnalController.php

public function update(Request $request, Jurnal $jurnal)
{
    // Pastikan pengguna hanya bisa mengubah jurnal miliknya sendiri
    if ($request->user()->id !== $jurnal->user_id) {
        return response()->json(['message' => 'Akses ditolak'], 403);
    }

    // 1. Tambahkan validasi untuk foto (opsional, harus berupa gambar)
    $validator = Validator::make($request->all(), [
    'judul' => 'sometimes|string|max:255',
    'deskripsi' => 'sometimes|string',
    'tanggal' => 'sometimes|date_format:Y-m-d H:i',
    'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
    }
    
    // 2. Siapkan data teks untuk diupdate
    $dataToUpdate = $request->only(['judul', 'deskripsi', 'tanggal']);

    // 3. Cek jika ada file foto baru di request
    if ($request->hasFile('foto')) {
        // 4. Hapus foto lama dari storage jika ada
        if ($jurnal->foto) {
            Storage::delete($jurnal->foto);
        }

        // 5. Simpan foto baru dan dapatkan path-nya
        $path = $request->file('foto')->store('public/fotos');
        $dataToUpdate['foto'] = $path; // Tambahkan path foto baru ke data
    }
    
    // 6. Update jurnal dengan data baru
    $jurnal->update($dataToUpdate);

    return response()->json($jurnal);
}

    /**
     * Menghapus data jurnal.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Jurnal  $jurnal
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Jurnal $jurnal)
    {
        // Pastikan pengguna hanya bisa menghapus jurnal miliknya sendiri
        if ($request->user()->id !== $jurnal->user_id) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        // Hapus file foto dari storage jika ada
        if ($jurnal->foto) {
            $path = str_replace('/storage', 'public', $jurnal->foto);
            Storage::delete($path);
        }
        
        $jurnal->delete();

        return response()->json(['message' => 'Jurnal berhasil dihapus']);
    }

     public function showPhoto(Request $request, Jurnal $jurnal)
    {
        // 1. Pengecekan Keamanan: Pastikan user adalah pemilik jurnal
        if ($request->user()->id !== $jurnal->user_id) {
            abort(403, 'Akses ditolak.');
        }

        // 2. Cek apakah jurnal memiliki path foto dan filenya ada di storage
        if (!$jurnal->foto || !Storage::exists($jurnal->foto)) {
            abort(404, 'Foto jurnal tidak ditemukan.');
        }
        
        // 3. Kembalikan file gambar sebagai respons
        return response()->file(Storage::path($jurnal->foto));
    }
}