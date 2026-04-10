<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * PostController — CRUD postingan + like, repost, simpan, dan feed following.
 */
class PostController extends Controller
{
    /**
     * Daftar semua postingan (feed umum), diurutkan terbaru.
     * Mendukung filter hashtag via query param ?hashtag=xxx
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Post::with([
            'user:id,name,username,profile_photo',
            'comments.user:id,name,username,profile_photo',
        ])->orderBy('created_at', 'desc');

        if ($request->filled('hashtag')) {
            $query->withHashtag($request->hashtag);
        }

        $posts = $query->get();
        return response()->json([
            'success' => true,
            'data'    => $posts->map(fn($p) => $this->formatPost($p, $user)),
        ]);
    }

    /**
     * Feed postingan dari orang-orang yang diikuti user.
     * Hanya menampilkan postingan dari following list.
     */
    public function followingFeed(Request $request): JsonResponse
    {
        $user = $request->user();

        // Ambil ID semua user yang diikuti
        $followingIds = $user->following()->pluck('users.id');

        if ($followingIds->isEmpty()) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $query = Post::with([
            'user:id,name,username,profile_photo',
            'comments.user:id,name,username,profile_photo',
        ])
        ->whereIn('user_id', $followingIds)
        ->orderBy('created_at', 'desc');

        if ($request->filled('hashtag')) {
            $query->withHashtag($request->hashtag);
        }

        $posts = $query->get();
        return response()->json([
            'success' => true,
            'data'    => $posts->map(fn($p) => $this->formatPost($p, $user)),
        ]);
    }

    /** Buat postingan baru */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:250',
            'image'   => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'file'    => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = ['user_id' => $request->user()->id, 'content' => $request->content];
        if ($request->hasFile('image')) $data['image'] = $request->file('image')->store('posts/images', 'public');
        if ($request->hasFile('file')) {
            $data['file']      = $request->file('file')->store('posts/files', 'public');
            $data['file_name'] = $request->file('file')->getClientOriginalName();
        }

        $post = Post::create($data);
        $post->load('user:id,name,username,profile_photo');

        return response()->json([
            'success' => true,
            'message' => 'Postingan berhasil dibuat.',
            'data'    => $this->formatPost($post, $request->user()),
        ], 201);
    }

    /** Update postingan */
    public function update(Request $request, Post $post): JsonResponse
    {
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Tidak memiliki izin.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'sometimes|required|string|max:250',
            'image'   => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'file'    => 'nullable|file|max:10240',
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        if ($request->has('content')) $post->content = $request->content;
        if ($request->hasFile('image')) {
            if ($post->image) Storage::disk('public')->delete($post->image);
            $post->image = $request->file('image')->store('posts/images', 'public');
        }
        if ($request->input('remove_image') === 'true' && $post->image) {
            Storage::disk('public')->delete($post->image);
            $post->image = null;
        }
        if ($request->hasFile('file')) {
            if ($post->file) Storage::disk('public')->delete($post->file);
            $post->file      = $request->file('file')->store('posts/files', 'public');
            $post->file_name = $request->file('file')->getClientOriginalName();
        }
        if ($request->input('remove_file') === 'true' && $post->file) {
            Storage::disk('public')->delete($post->file);
            $post->file = $post->file_name = null;
        }

        $post->save();
        $post->load(['user:id,name,username,profile_photo', 'comments.user:id,name,username,profile_photo']);

        return response()->json([
            'success' => true,
            'message' => 'Postingan berhasil diperbarui.',
            'data'    => $this->formatPost($post, $request->user()),
        ]);
    }

    /** Hapus postingan */
    public function destroy(Request $request, Post $post): JsonResponse
    {
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Tidak memiliki izin.'], 403);
        }
        if ($post->image) Storage::disk('public')->delete($post->image);
        if ($post->file)  Storage::disk('public')->delete($post->file);
        foreach ($post->comments as $c) {
            if ($c->image) Storage::disk('public')->delete($c->image);
            if ($c->file)  Storage::disk('public')->delete($c->file);
        }
        $post->delete();
        return response()->json(['success' => true, 'message' => 'Postingan berhasil dihapus.']);
    }

    /**
     * Toggle like pada postingan.
     * Jika sudah like → unlike. Jika belum → like dan kirim notifikasi.
     */
    public function toggleLike(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if ($post->likedBy()->where('user_id', $user->id)->exists()) {
            // Unlike
            $post->likedBy()->detach($user->id);
            $liked = false;
            // Hapus notifikasi like yang sudah ada
            Notification::where([
                'user_id'  => $post->user_id,
                'actor_id' => $user->id,
                'type'     => 'like',
                'post_id'  => $post->id,
            ])->delete();
        } else {
            // Like
            $post->likedBy()->syncWithoutDetaching([$user->id]);
            $liked = true;
            // Kirim notifikasi ke pemilik postingan (kecuali like sendiri)
            if ($post->user_id !== $user->id) {
                Notification::firstOrCreate([
                    'user_id'  => $post->user_id,
                    'actor_id' => $user->id,
                    'type'     => 'like',
                    'post_id'  => $post->id,
                ]);
            }
        }

        return response()->json([
            'success'     => true,
            'liked'       => $liked,
            'likes_count' => $post->likedBy()->count(),
        ]);
    }

    /**
     * Toggle repost pada postingan.
     * Jika sudah repost → batalkan. Jika belum → repost dan kirim notifikasi.
     */
    public function toggleRepost(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if ($post->repostedBy()->where('user_id', $user->id)->exists()) {
            $post->repostedBy()->detach($user->id);
            $reposted = false;
            Notification::where([
                'user_id'  => $post->user_id,
                'actor_id' => $user->id,
                'type'     => 'repost',
                'post_id'  => $post->id,
            ])->delete();
        } else {
            $post->repostedBy()->syncWithoutDetaching([$user->id]);
            $reposted = true;
            if ($post->user_id !== $user->id) {
                Notification::firstOrCreate([
                    'user_id'  => $post->user_id,
                    'actor_id' => $user->id,
                    'type'     => 'repost',
                    'post_id'  => $post->id,
                ]);
            }
        }

        return response()->json([
            'success'        => true,
            'reposted'       => $reposted,
            'reposts_count'  => $post->repostedBy()->count(),
        ]);
    }

    /**
     * Toggle simpan (bookmark) postingan.
     */
    public function toggleSave(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if ($post->savedBy()->where('user_id', $user->id)->exists()) {
            $post->savedBy()->detach($user->id);
            $saved = false;
        } else {
            $post->savedBy()->syncWithoutDetaching([$user->id]);
            $saved = true;
        }

        return response()->json([
            'success' => true,
            'saved'   => $saved,
        ]);
    }

    /**
     * Mendapatkan semua postingan yang disimpan oleh user yang login.
     */
    public function savedPosts(Request $request): JsonResponse
    {
        $user  = $request->user();
        $posts = $user->savedPosts()
            ->with([
                'user:id,name,username,profile_photo',
                'comments.user:id,name,username,profile_photo',
            ])
            ->orderByPivot('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $posts->map(fn($p) => $this->formatPost($p, $user)),
        ]);
    }

    /**
     * Format postingan menjadi array konsisten untuk respons API.
     * Menyertakan status like/repost/saved dari user yang sedang login.
     *
     * @param Post $post - Instance postingan
     * @param \App\Models\User $currentUser - User yang sedang login
     * @return array
     */
    private function formatPost(Post $post, $currentUser): array
    {
        return [
            'id'           => $post->id,
            'content'      => $post->content,
            'image_url'    => $post->image_url,
            'file_url'     => $post->file_url,
            'file_name'    => $post->file_name,
            'user'         => $post->user ? [
                'id'                => $post->user->id,
                'name'              => $post->user->name,
                'username'          => $post->user->username,
                'profile_photo_url' => $post->user->profile_photo_url,
            ] : null,
            'comments'      => $post->relationLoaded('comments')
                ? $post->comments->map(fn($c) => $this->formatComment($c))
                : [],
            // Statistik interaksi
            'likes_count'   => $post->likedBy()->count(),
            'reposts_count' => $post->repostedBy()->count(),
            'saves_count'   => $post->savedBy()->count(),
            // Status interaksi user yang login
            'is_liked'      => $post->likedBy()->where('user_id', $currentUser->id)->exists(),
            'is_reposted'   => $post->repostedBy()->where('user_id', $currentUser->id)->exists(),
            'is_saved'      => $post->savedBy()->where('user_id', $currentUser->id)->exists(),
            'created_at'    => $post->created_at,
            'updated_at'    => $post->updated_at,
        ];
    }

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
