<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration untuk membuat tabel follows.
 * Tabel ini menyimpan relasi following/follower antar pengguna.
 * Satu baris = satu user (follower) mengikuti user lain (following).
 */
return new class extends Migration
{
    /**
     * Membuat tabel follows dengan kolom follower_id dan following_id.
     * Kombinasi keduanya harus unik untuk mencegah follow duplikat.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->id();

            // User yang melakukan follow
            $table->foreignId('follower_id')
                ->constrained('users')
                ->onDelete('cascade');

            // User yang difollow
            $table->foreignId('following_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Pastikan tidak ada follow duplikat dari user yang sama
            $table->unique(['follower_id', 'following_id']);

            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel follows saat rollback.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
