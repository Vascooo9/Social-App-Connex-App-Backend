<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration untuk membuat tabel posts.
 * Tabel ini menyimpan semua postingan pengguna dalam aplikasi social media.
 */
return new class extends Migration
{
    /**
     * Membuat tabel posts dengan semua kolom yang diperlukan.
     * Setiap postingan terhubung ke user melalui foreign key user_id.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            // Foreign key ke tabel users dengan cascade delete
            // Jika user dihapus, semua postingannya ikut terhapus
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // Konten teks postingan, maksimum 250 karakter
            $table->string('content', 250);

            // Path gambar di storage (nullable jika tidak ada gambar)
            $table->string('image')->nullable();

            // Path file lampiran di storage (nullable jika tidak ada file)
            $table->string('file')->nullable();

            // Nama asli file lampiran untuk ditampilkan ke user
            $table->string('file_name')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel posts saat rollback.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
