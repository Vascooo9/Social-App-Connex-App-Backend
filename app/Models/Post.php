<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Post merepresentasikan postingan dalam aplikasi social media.
 * Setiap postingan dapat mengandung teks, hashtag, gambar, dan file lampiran.
 *
 * @property int $id
 * @property int $user_id
 * @property string $content
 * @property string|null $image
 * @property string|null $file
 * @property string|null $file_name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Post extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal.
     * Kolom-kolom ini aman diisi melalui metode create() atau fill().
     */
    protected $fillable = [
        'user_id',
        'content',
        'image',
        'file',
        'file_name',
    ];

    /**
     * Relasi many-to-one: setiap postingan dimiliki oleh satu user.
     * Mengembalikan user pemilik postingan ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi one-to-many: satu postingan dapat memiliki banyak komentar.
     * Komentar diurutkan dari yang terbaru (descending by created_at).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }

    /**
     * Scope untuk memfilter postingan berdasarkan hashtag tertentu.
     * Mencari hashtag dalam konten postingan menggunakan LIKE query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $hashtag - Kata kunci hashtag tanpa simbol '#'
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithHashtag($query, string $hashtag)
    {
        return $query->where('content', 'LIKE', '%#' . $hashtag . '%');
    }

    /**
     * Accessor untuk mendapatkan URL lengkap gambar postingan.
     * Mengembalikan URL asset storage jika gambar ada, null jika tidak.
     *
     * @return string|null
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return null;
    }

    /**
     * Accessor untuk mendapatkan URL lengkap file lampiran postingan.
     * Mengembalikan URL asset storage jika file ada, null jika tidak.
     *
     * @return string|null
     */
    public function getFileUrlAttribute(): ?string
    {
        if ($this->file) {
            return asset('storage/' . $this->file);
        }
        return null;
    }
}
