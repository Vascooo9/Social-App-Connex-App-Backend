<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * CommentController menangani operasi CRUD untuk komentar pada postingan.
 * Menyediakan endpoint untuk membuat, memperbarui, dan menghapus komentar
 * beserta dukungan upload gambar dan file lampiran.
 */
class CommentController extends Controller
{
    /**
     * Membuat komentar baru pada postingan tertentu.
     * Mendukung upload gambar dan file lampiran bersamaan dengan teks komentar.
     *
     * @param Request $request - HTTP request berisi content, image (opsional), file (opsional)
     * @param Post $post - Instance postingan induk (route model binding)
     * @return JsonResponse - Data komentar yang baru dibuat atau error validasi
     */
    public function store(Request $request, Post $post): JsonResponse
    {
        // Validasi input komentar baru
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
            'post_id' => $post->id,
            'content' => $request->content,
        ];

        // Proses upload gambar komentar jika ada
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')
                ->store('comments/images', 'public');
        }

        // Proses upload file lampiran komentar jika ada
        if ($request->hasFile('file')) {
            $data['file']      = $request->file('file')
                ->store('comments/files', 'public');
            $data['file_name'] = $request->file('file')
                ->getClientOriginalName();
        }

        // Buat komentar baru di database
        $comment = Comment::create($data);

        // Load relasi user untuk respons
        $comment->load('user:id,name,username,profile_photo');

        return response()->json([
            'success' => true,
            'message' => 'Komentar berhasil dibuat.',
            'data'    => $this->formatComment($comment),
        ], 201);
    }

    /**
     * Memperbarui komentar yang sudah ada milik pengguna yang sedang login.
     * Hanya pemilik komentar yang dapat memperbarui komentarnya.
     *
     * @param Request $request - HTTP request berisi data yang ingin diperbarui
     * @param Comment $comment - Instance komentar yang ingin diperbarui (route model binding)
     * @return JsonResponse - Data komentar yang sudah diperbarui atau error
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        // Cek otorisasi: hanya pemilik yang boleh memperbarui komentar
        if ($comment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk memperbarui komentar ini.',
            ], 403);
        }

        // Validasi input pembaruan komentar
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
            $comment->content = $request->content;
        }

        // Proses gambar baru: hapus gambar lama, simpan yang baru
        if ($request->hasFile('image')) {
            if ($comment->image) {
                Storage::disk('public')->delete($comment->image);
            }
            $comment->image = $request->file('image')
                ->store('comments/images', 'public');
        }

        // Hapus gambar jika diminta
        if ($request->input('remove_image') === 'true' && $comment->image) {
            Storage::disk('public')->delete($comment->image);
            $comment->image = null;
        }

        // Proses file lampiran baru: hapus file lama, simpan yang baru
        if ($request->hasFile('file')) {
            if ($comment->file) {
                Storage::disk('public')->delete($comment->file);
            }
            $comment->file      = $request->file('file')
                ->store('comments/files', 'public');
            $comment->file_name = $request->file('file')
                ->getClientOriginalName();
        }

        // Hapus file lampiran jika diminta
        if ($request->input('remove_file') === 'true' && $comment->file) {
            Storage::disk('public')->delete($comment->file);
            $comment->file      = null;
            $comment->file_name = null;
        }

        $comment->save();
        $comment->load('user:id,name,username,profile_photo');

        return response()->json([
            'success' => true,
            'message' => 'Komentar berhasil diperbarui.',
            'data'    => $this->formatComment($comment),
        ]);
    }

    /**
     * Menghapus komentar beserta semua file terkait dari storage.
     * Hanya pemilik komentar yang dapat menghapus komentarnya.
     *
     * @param Request $request - HTTP request dengan token autentikasi
     * @param Comment $comment - Instance komentar yang ingin dihapus (route model binding)
     * @return JsonResponse - Pesan sukses setelah komentar dihapus
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        // Cek otorisasi: hanya pemilik yang boleh menghapus komentar
        if ($comment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk menghapus komentar ini.',
            ], 403);
        }

        // Hapus file gambar dari storage jika ada
        if ($comment->image) {
            Storage::disk('public')->delete($comment->image);
        }

        // Hapus file lampiran dari storage jika ada
        if ($comment->file) {
            Storage::disk('public')->delete($comment->file);
        }

        // Hapus komentar dari database
        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Komentar berhasil dihapus.',
        ]);
    }

    /**
     * Memformat data komentar menjadi array yang konsisten untuk respons API.
     * Menyertakan URL lengkap untuk gambar, file, dan foto profil user.
     *
     * @param Comment $comment - Instance model Comment dengan relasi yang sudah di-load
     * @return array - Array data komentar yang sudah diformat
     */
    private function formatComment(Comment $comment): array
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
