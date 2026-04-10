<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Post — Postingan dengan dukungan like, repost, dan simpan.
 */
class Post extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'content', 'image', 'file', 'file_name'];

    /** Relasi ke user pemilik postingan */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Relasi ke komentar, diurutkan terbaru */
    public function comments()
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }

    /**
     * Relasi many-to-many ke user yang menyukai postingan ini.
     * Menggunakan tabel pivot 'likes'.
     */
    public function likedBy()
    {
        return $this->belongsToMany(User::class, 'likes')->withTimestamps();
    }

    /**
     * Relasi many-to-many ke user yang melakukan repost.
     * Menggunakan tabel pivot 'reposts'.
     */
    public function repostedBy()
    {
        return $this->belongsToMany(User::class, 'reposts')->withTimestamps();
    }

    /**
     * Relasi many-to-many ke user yang menyimpan postingan ini.
     * Menggunakan tabel pivot 'saved_posts'.
     */
    public function savedBy()
    {
        return $this->belongsToMany(User::class, 'saved_posts')->withTimestamps();
    }

    /** Scope filter hashtag */
    public function scopeWithHashtag($query, string $hashtag)
    {
        return $query->where('content', 'LIKE', '%#' . $hashtag . '%');
    }

    /** Accessor URL gambar */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    /** Accessor URL file */
    public function getFileUrlAttribute(): ?string
    {
        return $this->file ? asset('storage/' . $this->file) : null;
    }
}
