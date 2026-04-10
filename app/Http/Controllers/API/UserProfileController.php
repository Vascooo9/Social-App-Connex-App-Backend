<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * UserProfileController menangani endpoint untuk melihat profil pengguna lain.
 * Menyertakan data followers, following, dan status apakah user yang login
 * sedang mengikuti profil yang dilihat.
 */
class UserProfileController extends Controller
{
    /**
     * Mengembalikan data profil publik pengguna beserta postingan dan data sosial.
     *
     * @param Request $request - HTTP request dengan token autentikasi
     * @param string $username - Username pengguna yang ingin dilihat
     * @return JsonResponse - Data profil lengkap termasuk follow stats
     */
    public function show(Request $request, string $username): JsonResponse
    {
        $user        = User::where('username', $username)->firstOrFail();
        $currentUser = $request->user();

        // Ambil postingan user dengan relasi
        $posts = $user->posts()
            ->with([
                'user:id,name,username,profile_photo',
                'comments.user:id,name,username,profile_photo',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // Format postingan
        $formattedPosts = $posts->map(function ($post) {
            return [
                'id'        => $post->id,
                'content'   => $post->content,
                'image_url' => $post->image_url,
                'file_url'  => $post->file_url,
                'file_name' => $post->file_name,
                'user'      => [
                    'id'                => $post->user->id,
                    'name'              => $post->user->name,
                    'username'          => $post->user->username,
                    'profile_photo_url' => $post->user->profile_photo_url,
                ],
                'comments'   => $post->comments->map(function ($c) {
                    return [
                        'id'        => $c->id,
                        'content'   => $c->content,
                        'image_url' => $c->image_url,
                        'file_url'  => $c->file_url,
                        'file_name' => $c->file_name,
                        'user'      => [
                            'id'                => $c->user->id,
                            'name'              => $c->user->name,
                            'username'          => $c->user->username,
                            'profile_photo_url' => $c->user->profile_photo_url,
                        ],
                        'post_id'    => $c->post_id,
                        'created_at' => $c->created_at,
                        'updated_at' => $c->updated_at,
                    ];
                }),
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'user'  => [
                    'id'                => $user->id,
                    'name'              => $user->name,
                    'username'          => $user->username,
                    'bio'               => $user->bio,
                    'profile_photo_url' => $user->profile_photo_url,
                    'created_at'        => $user->created_at,
                    'posts_count'       => $posts->count(),
                    // Jumlah followers dan following
                    'followers_count'   => $user->followers()->count(),
                    'following_count'   => $user->following()->count(),
                    // Apakah user yang login sedang mengikuti profil ini
                    'is_following'      => $currentUser->isFollowing($user->id),
                    // Apakah profil ini adalah milik user yang login
                    'is_own_profile'    => $currentUser->id === $user->id,
                ],
                'posts' => $formattedPosts,
            ],
        ]);
    }
}
