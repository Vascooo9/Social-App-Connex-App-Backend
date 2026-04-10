<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration untuk membuat tabel notifications.
 * Menyimpan notifikasi untuk setiap user (like, follow, komentar, repost).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            // User penerima notifikasi
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // User yang memicu notifikasi (yang melakukan aksi)
            $table->foreignId('actor_id')->constrained('users')->onDelete('cascade');
            // Jenis notifikasi: like, follow, comment, repost
            $table->enum('type', ['like', 'follow', 'comment', 'repost']);
            // Referensi ke postingan terkait (nullable untuk notif follow)
            $table->foreignId('post_id')->nullable()->constrained()->onDelete('cascade');
            // Referensi ke komentar terkait (nullable)
            $table->foreignId('comment_id')->nullable()->constrained()->onDelete('cascade');
            // Status sudah dibaca atau belum
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
