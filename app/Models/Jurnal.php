<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Jurnal extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'judul', 'deskripsi', 'tanggal', 'foto'];
    protected $casts = [
        'tanggal' => 'datetime',
    ];
    protected $appends = ['foto_url'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFotoUrlAttribute()
    {
        if ($this->foto) {
            // Membuat URL lengkap ke rute baru kita
            return url('/api/jurnals/' . $this->id . '/foto');
        }
        return null;
    }
}
