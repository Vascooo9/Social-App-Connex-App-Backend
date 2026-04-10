<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * FollowController menangani operasi follow dan unfollow antar pengguna.
 * Menyediakan endpoint untuk mengikuti, berhenti mengikuti,
 * dan mendapatkan daftar followers/following.
 */
class FollowController extends Controller
{
    /**
     * Mengikuti pengguna lain (follow).
     * User tidak dapat mengikuti dirinya sendiri.
     * Jika sudah mengikuti, akan mengembalikan pesan tanpa error.
     *
     * @param Request $request - HTTP request dengan token autentikasi
     * @param string $username - Username user yang ingin diikuti
     * @return JsonResponse - Status follow dan jumlah followers terbaru
     */
    public function follow(Request $request, string $username): JsonResponse
    {
        // Cari user target berdasarkan username
        $targetUser = User::where('username', $username)->firstOrFail();
        $currentUser = $request->user();

        // Cegah user mengikuti dirinya sendiri
        if ($currentUser->id === $targetUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat mengikuti diri sendiri.',
            ], 422);
        }

        // Tambahkan ke daftar following jika belum mengikuti (syncWithoutDetaching)
        $currentUser->following()->syncWithoutDetaching([$targetUser->id]);

        return response()->json([
            'success'         => true,
            'message'         => 'Berhasil mengikuti @' . $targetUser->username,
            'is_following'    => true,
            'followers_count' => $targetUser->followers()->count(),
        ]);
    }

    /**
     * Berhenti mengikuti pengguna (unfollow).
     * Menghapus relasi follow dari tabel pivot.
     *
     * @param Request $request - HTTP request dengan token autentikasi
     * @param string $username - Username user yang ingin di-unfollow
     * @return JsonResponse - Status follow dan jumlah followers terbaru
     */
    public function unfollow(Request $request, string $username): JsonResponse
    {
        // Cari user target berdasarkan username
        $targetUser = User::where('username', $username)->firstOrFail();
        $currentUser = $request->user();

        // Hapus dari daftar following
        $currentUser->following()->detach($targetUser->id);

        return response()->json([
            'success'         => true,
            'message'         => 'Berhenti mengikuti @' . $targetUser->username,
            'is_following'    => false,
            'followers_count' => $targetUser->followers()->count(),
        ]);
    }

    /**
     * Mendapatkan daftar followers (pengikut) dari user tertentu.
     *
     * @param string $username - Username user yang ingin dilihat followersnya
     * @return JsonResponse - Daftar user yang mengikuti
     */
    public function followers(string $username): JsonResponse
    {
        $user = User::where('username', $username)->firstOrFail();

        $followers = $user->followers()
            ->select('users.id', 'users.name', 'users.username', 'users.profile_photo', 'users.bio')
            ->get()
            ->map(fn($u) => [
                'id'                => $u->id,
                'name'              => $u->name,
                'username'          => $u->username,
                'bio'               => $u->bio,
                'profile_photo_url' => $u->profile_photo_url,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $followers,
        ]);
    }

    /**
     * Mendapatkan daftar following (yang diikuti) dari user tertentu.
     *
     * @param string $username - Username user yang ingin dilihat followingnya
     * @return JsonResponse - Daftar user yang diikuti
     */
    public function following(string $username): JsonResponse
    {
        $user = User::where('username', $username)->firstOrFail();

        $following = $user->following()
            ->select('users.id', 'users.name', 'users.username', 'users.profile_photo', 'users.bio')
            ->get()
            ->map(fn($u) => [
                'id'                => $u->id,
                'name'              => $u->name,
                'username'          => $u->username,
                'bio'               => $u->bio,
                'profile_photo_url' => $u->profile_photo_url,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $following,
        ]);
    }
}
