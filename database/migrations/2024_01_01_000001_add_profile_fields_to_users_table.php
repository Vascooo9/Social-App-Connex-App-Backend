<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration untuk memodifikasi tabel users yang sudah ada.
 * Menambahkan kolom username, bio, dan profile_photo untuk fitur social media.
 */
return new class extends Migration
{
    /**
     * Menjalankan migrasi: menambahkan kolom baru ke tabel users.
     * - username: nama pengguna unik yang ditampilkan di profil
     * - bio: deskripsi singkat pengguna (maks 160 karakter)
     * - profile_photo: path file foto profil di storage
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Username unik untuk identitas pengguna
            $table->string('username', 50)->unique()->after('name')->nullable();
            // Bio singkat pengguna seperti di Twitter
            $table->string('bio', 160)->nullable()->after('username');
            // Path foto profil di storage (nullable jika belum upload)
            $table->string('profile_photo')->nullable()->after('bio');
        });
    }

    /**
     * Membalikkan migrasi: menghapus kolom yang ditambahkan.
     * Digunakan saat rollback migrasi.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'bio', 'profile_photo']);
        });
    }
};
