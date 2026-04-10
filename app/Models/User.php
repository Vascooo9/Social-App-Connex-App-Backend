<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Model User — dilengkapi relasi like, repost, simpan, following, followers.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'username', 'email', 'password', 'bio', 'profile_photo'];
    protected $hidden   = ['password', 'remember_token'];
    protected $casts    = ['email_verified_at' => 'datetime', 'password' => 'hashed'];

    public function posts()    { return $this->hasMany(Post::class); }
    public function comments() { return $this->hasMany(Comment::class); }

    /** User yang diikuti oleh user ini */
    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')->withTimestamps();
    }

    /** User yang mengikuti user ini */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')->withTimestamps();
    }

    /** Postingan yang di-like user ini */
    public function likedPosts()
    {
        return $this->belongsToMany(Post::class, 'likes')->withTimestamps();
    }

    /** Postingan yang di-repost user ini */
    public function repostedPosts()
    {
        return $this->belongsToMany(Post::class, 'reposts')->withTimestamps();
    }

    /** Postingan yang disimpan/bookmark user ini */
    public function savedPosts()
    {
        return $this->belongsToMany(Post::class, 'saved_posts')->withTimestamps();
    }

    /** Cek apakah user ini sedang mengikuti user lain */
    public function isFollowing(int $userId): bool
    {
        return $this->following()->where('following_id', $userId)->exists();
    }

    /** Accessor URL foto profil */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo ? asset('storage/' . $this->profile_photo) : null;
    }
}
