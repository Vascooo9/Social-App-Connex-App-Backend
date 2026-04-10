<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Model User merepresentasikan pengguna dalam aplikasi social media.
 * Dilengkapi dengan relasi following/followers untuk fitur sosial.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'bio',
        'profile_photo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    /**
     * Relasi one-to-many: satu user dapat memiliki banyak postingan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Relasi one-to-many: satu user dapat memiliki banyak komentar.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Relasi many-to-many: daftar user yang diikuti oleh user ini.
     * Menggunakan tabel pivot 'follows' dengan kolom follower_id dan following_id.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function following()
    {
        return $this->belongsToMany(
            User::class,
            'follows',
            'follower_id',
            'following_id'
        )->withTimestamps();
    }

    /**
     * Relasi many-to-many: daftar user yang mengikuti user ini (followers).
     * Menggunakan tabel pivot 'follows' dengan kolom following_id dan follower_id.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function followers()
    {
        return $this->belongsToMany(
            User::class,
            'follows',
            'following_id',
            'follower_id'
        )->withTimestamps();
    }

    /**
     * Mengecek apakah user ini sedang mengikuti user lain.
     * Digunakan untuk menentukan tampilan tombol Follow/Unfollow.
     *
     * @param int $userId - ID user yang ingin dicek
     * @return bool - True jika sedang mengikuti, false jika tidak
     */
    public function isFollowing(int $userId): bool
    {
        return $this->following()->where('following_id', $userId)->exists();
    }

    /**
     * Accessor untuk mendapatkan URL lengkap foto profil.
     *
     * @return string|null
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if ($this->profile_photo) {
            return asset('storage/' . $this->profile_photo);
        }
        return null;
    }
}
