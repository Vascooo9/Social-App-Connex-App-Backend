<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Comment merepresentasikan komentar pada postingan di aplikasi social media.
 * Setiap komentar dapat mengandung teks, hashtag, gambar, dan file lampiran.
 *
 * @property int $id
 * @property int $user_id
 * @property int $post_id
 * @property string $content
 * @property string|null $image
 * @property string|null $file
 * @property string|null $file_name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Comment extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal.
     * Kolom-kolom ini aman diisi melalui metode create() atau fill().
     */
    protected $fillable = [
        'user_id',
        'post_id',
        'content',
        'image',
        'file',
        'file_name',
    ];

    /**
     * Relasi many-to-one: setiap komentar dimiliki oleh satu user.
     * Mengembalikan user yang membuat komentar ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi many-to-one: setiap komentar terhubung ke satu postingan.
     * Mengembalikan postingan induk dari komentar ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Scope untuk memfilter komentar berdasarkan hashtag tertentu.
     * Mencari hashtag dalam konten komentar menggunakan LIKE query.
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
     * Accessor untuk mendapatkan URL lengkap gambar komentar.
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
     * Accessor untuk mendapatkan URL lengkap file lampiran komentar.
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
