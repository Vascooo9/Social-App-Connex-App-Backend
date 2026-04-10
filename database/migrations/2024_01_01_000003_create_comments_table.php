<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration untuk membuat tabel comments.
 * Tabel ini menyimpan semua komentar pada postingan dalam aplikasi social media.
 */
return new class extends Migration
{
    /**
     * Membuat tabel comments dengan semua kolom yang diperlukan.
     * Setiap komentar terhubung ke user dan post melalui foreign key.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            // Foreign key ke tabel users, cascade delete jika user dihapus
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // Foreign key ke tabel posts, cascade delete jika postingan dihapus
            $table->foreignId('post_id')
                ->constrained()
                ->onDelete('cascade');

            // Konten teks komentar, maksimum 250 karakter
            $table->string('content', 250);

            // Path gambar komentar di storage (nullable)
            $table->string('image')->nullable();

            // Path file lampiran komentar di storage (nullable)
            $table->string('file')->nullable();

            // Nama asli file lampiran untuk ditampilkan ke user
            $table->string('file_name')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel comments saat rollback.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
