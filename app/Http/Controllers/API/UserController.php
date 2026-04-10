<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * UserController menangani operasi terkait profil pengguna.
 * Menyediakan endpoint untuk memperbarui bio, nama, dan foto profil user.
 */
class UserController extends Controller
{
    /**
     * Memperbarui informasi profil pengguna yang sedang login.
     * Dapat memperbarui nama, username, bio, dan foto profil sekaligus.
     *
     * @param Request $request - HTTP request yang berisi data profil yang ingin diubah
     * @return JsonResponse - Data user yang sudah diperbarui atau error validasi
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        // Validasi input pembaruan profil
        $validator = Validator::make($request->all(), [
            'name'          => 'sometimes|required|string|max:255',
            'username'      => 'sometimes|required|string|max:50|alpha_dash|unique:users,username,' . $user->id,
            'bio'           => 'nullable|string|max:160',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Proses upload foto profil jika ada file baru
        if ($request->hasFile('profile_photo')) {
            // Hapus foto lama dari storage jika ada
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            // Simpan foto baru ke direktori profile_photos
            $path = $request->file('profile_photo')
                ->store('profile_photos', 'public');
            $user->profile_photo = $path;
        }

        // Update field lain jika dikirim dalam request
        if ($request->has('name'))     $user->name     = $request->name;
        if ($request->has('username')) $user->username = $request->username;
        if ($request->has('bio'))      $user->bio      = $request->bio;

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'user'    => [
                'id'                => $user->id,
                'name'              => $user->name,
                'username'          => $user->username,
                'email'             => $user->email,
                'bio'               => $user->bio,
                'profile_photo_url' => $user->profile_photo_url,
                'created_at'        => $user->created_at,
            ],
        ]);
    }

    /**
     * Menghapus foto profil pengguna yang sedang login.
     * Menghapus file dari storage dan mereset kolom profile_photo di database.
     *
     * @param Request $request - HTTP request dengan token autentikasi di header
     * @return JsonResponse - Pesan sukses setelah foto profil dihapus
     */
    public function removeProfilePhoto(Request $request): JsonResponse
    {
        $user = $request->user();

        // Hapus file foto dari storage jika ada
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
            $user->profile_photo = null;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil dihapus.',
        ]);
    }
}
