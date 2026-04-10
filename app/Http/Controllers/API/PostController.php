<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * PostController menangani operasi CRUD untuk postingan.
 * Menyediakan endpoint untuk membuat, membaca, memperbarui, dan menghapus postingan
 * beserta dukungan upload gambar dan file lampiran.
 */
class PostController extends Controller
{
    /**
     * Mengembalikan semua postingan yang diurutkan dari yang terbaru.
     * Menyertakan data user pemilik dan komentar beserta user komentar.
     * Mendukung filter berdasarkan hashtag melalui query parameter.
     *
     * @param Request $request - HTTP request, bisa berisi query param 'hashtag'
     * @return JsonResponse - Daftar postingan dengan relasi user dan komentar
     */
    public function index(Request $request): JsonResponse
    {
        // Query dasar: ambil postingan dengan eager loading relasi user dan komentar
        $query = Post::with([
            'user:id,name,username,profile_photo',
            'comments.user:id,name,username,profile_photo',
        ])->orderBy('created_at', 'desc');

        // Terapkan filter hashtag jika parameter 'hashtag' ada di request
        if ($request->has('hashtag') && $request->hashtag !== '') {
            $query->withHashtag($request->hashtag);
        }

        $posts = $query->get();

        // Format setiap postingan dengan URL yang lengkap
        $formatted = $posts->map(fn($post) => $this->formatPost($post));

        return response()->json([
            'success' => true,
            'data'    => $formatted,
        ]);
    }

    /**
     * Membuat postingan baru oleh pengguna yang sedang login.
     * Mendukung upload gambar dan file lampiran bersamaan dengan teks.
     *
     * @param Request $request - HTTP request berisi content, image (opsional), file (opsional)
     * @return JsonResponse - Data postingan yang baru dibuat atau error validasi
     */
    public function store(Request $request): JsonResponse
    {
        // Validasi input postingan baru
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:250',
            'image'   => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'file'    => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = [
            'user_id' => $request->user()->id,
            'content' => $request->content,
        ];

        // Proses upload gambar jika ada
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('posts/images', 'public');
        }

        // Proses upload file lampiran jika ada, simpan nama asli file
        if ($request->hasFile('file')) {
            $data['file']      = $request->file('file')->store('posts/files', 'public');
            $data['file_name'] = $request->file('file')->getClientOriginalName();
        }

        // Buat postingan baru di database
        $post = Post::create($data);

        // Load relasi user untuk respons
        $post->load('user:id,name,username,profile_photo');

        return response()->json([
            'success' => true,
            'message' => 'Postingan berhasil dibuat.',
            'data'    => $this->formatPost($post),
        ], 201);
    }

    /**
     * Memperbarui postingan yang sudah ada milik pengguna yang sedang login.
     * Hanya pemilik postingan yang dapat memperbarui postingannya.
     * Dapat memperbarui teks, gambar, atau file lampiran.
     *
     * @param Request $request - HTTP request berisi data yang ingin diperbarui
     * @param Post $post - Instance postingan yang ingin diperbarui (route model binding)
     * @return JsonResponse - Data postingan yang sudah diperbarui atau error
     */
    public function update(Request $request, Post $post): JsonResponse
    {
        // Cek otorisasi: hanya pemilik yang boleh memperbarui postingan
        if ($post->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk memperbarui postingan ini.',
            ], 403);
        }

        // Validasi input pembaruan
        $validator = Validator::make($request->all(), [
            'content' => 'sometimes|required|string|max:250',
            'image'   => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'file'    => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Update konten teks jika dikirim
        if ($request->has('content')) {
            $post->content = $request->content;
        }

        // Proses gambar baru: hapus gambar lama, simpan yang baru
        if ($request->hasFile('image')) {
            if ($post->image) {
                Storage::disk('public')->delete($post->image);
            }
            $post->image = $request->file('image')->store('posts/images', 'public');
        }

        // Hapus gambar jika diminta dengan mengirim remove_image=true
        if ($request->input('remove_image') === 'true' && $post->image) {
            Storage::disk('public')->delete($post->image);
            $post->image = null;
        }

        // Proses file lampiran baru: hapus file lama, simpan yang baru
        if ($request->hasFile('file')) {
            if ($post->file) {
                Storage::disk('public')->delete($post->file);
            }
            $post->file      = $request->file('file')->store('posts/files', 'public');
            $post->file_name = $request->file('file')->getClientOriginalName();
        }

        // Hapus file lampiran jika diminta
        if ($request->input('remove_file') === 'true' && $post->file) {
            Storage::disk('public')->delete($post->file);
            $post->file      = null;
            $post->file_name = null;
        }

        $post->save();
        $post->load(['user:id,name,username,profile_photo', 'comments.user:id,name,username,profile_photo']);

        return response()->json([
            'success' => true,
            'message' => 'Postingan berhasil diperbarui.',
            'data'    => $this->formatPost($post),
        ]);
    }

    /**
     * Menghapus postingan beserta semua file terkait dari storage.
     * Hanya pemilik postingan yang dapat menghapus postingannya.
     * Komentar akan dihapus otomatis karena cascade delete di database.
     *
     * @param Request $request - HTTP request dengan token autentikasi
     * @param Post $post - Instance postingan yang ingin dihapus (route model binding)
     * @return JsonResponse - Pesan sukses setelah postingan dihapus
     */
    public function destroy(Request $request, Post $post): JsonResponse
    {
        // Cek otorisasi: hanya pemilik yang boleh menghapus postingan
        if ($post->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk menghapus postingan ini.',
            ], 403);
        }

        // Hapus file gambar dari storage jika ada
        if ($post->image) {
            Storage::disk('public')->delete($post->image);
        }

        // Hapus file lampiran dari storage jika ada
        if ($post->file) {
            Storage::disk('public')->delete($post->file);
        }

        // Hapus juga file-file dari komentar yang terkait
        foreach ($post->comments as $comment) {
            if ($comment->image) Storage::disk('public')->delete($comment->image);
            if ($comment->file)  Storage::disk('public')->delete($comment->file);
        }

        // Hapus postingan dari database (komentar ikut terhapus via cascade)
        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Postingan berhasil dihapus.',
        ]);
    }

    /**
     * Memformat data postingan menjadi array yang konsisten untuk respons API.
     * Menyertakan URL lengkap untuk gambar, file, dan foto profil user.
     *
     * @param Post $post - Instance model Post dengan relasi yang sudah di-load
     * @return array - Array data postingan yang sudah diformat
     */
    private function formatPost(Post $post): array
    {
        return [
            'id'        => $post->id,
            'content'   => $post->content,
            'image_url' => $post->image_url,
            'file_url'  => $post->file_url,
            'file_name' => $post->file_name,
            'user'      => $post->user ? [
                'id'                => $post->user->id,
                'name'              => $post->user->name,
                'username'          => $post->user->username,
                'profile_photo_url' => $post->user->profile_photo_url,
            ] : null,
            'comments'   => $post->relationLoaded('comments')
                ? $post->comments->map(fn($c) => $this->formatComment($c))
                : [],
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
        ];
    }

    /**
     * Memformat data komentar untuk digunakan dalam respons postingan.
     * Menyertakan URL lengkap untuk gambar, file, dan foto profil user.
     *
     * @param \App\Models\Comment $comment - Instance model Comment
     * @return array - Array data komentar yang sudah diformat
     */
    private function formatComment($comment): array
    {
        return [
            'id'        => $comment->id,
            'content'   => $comment->content,
            'image_url' => $comment->image_url,
            'file_url'  => $comment->file_url,
            'file_name' => $comment->file_name,
            'user'      => $comment->user ? [
                'id'                => $comment->user->id,
                'name'              => $comment->user->name,
                'username'          => $comment->user->username,
                'profile_photo_url' => $comment->user->profile_photo_url,
            ] : null,
            'post_id'    => $comment->post_id,
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at,
        ];
    }
}
