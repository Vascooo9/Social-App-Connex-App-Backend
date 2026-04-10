<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * AuthController menangani semua proses autentikasi pengguna.
 * Menyediakan endpoint untuk registrasi, login, logout, dan mendapatkan data user yang sedang login.
 */
class AuthController extends Controller
{
    /**
     * Mendaftarkan pengguna baru ke dalam sistem.
     * Memvalidasi input, membuat user baru, dan mengembalikan token API.
     *
     * @param Request $request - HTTP request yang berisi name, username, email, password
     * @return JsonResponse - Token API dan data user jika sukses, atau error validasi
     */
    public function register(Request $request): JsonResponse
    {
        // Validasi data input registrasi
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users|alpha_dash',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Kembalikan error validasi jika ada
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Buat user baru dengan password yang di-hash otomatis
        $user = User::create([
            'name'     => $request->name,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => $request->password,
        ]);

        // Generate token API untuk user yang baru dibuat
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil.',
            'token'   => $token,
            'user'    => $this->formatUser($user),
        ], 201);
    }

    /**
     * Mengautentikasi pengguna dan menghasilkan token API.
     * Menerima email/username dan password, mengembalikan token jika kredensial valid.
     *
     * @param Request $request - HTTP request yang berisi email dan password
     * @return JsonResponse - Token API dan data user jika sukses, atau error autentikasi
     */
    public function login(Request $request): JsonResponse
    {
        // Validasi input login
        $validator = Validator::make($request->all(), [
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Cek apakah kredensial valid menggunakan Auth facade
        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        // Ambil user yang berhasil login
        $user  = Auth::user();

        // Hapus token lama dan buat token baru untuk keamanan
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'token'   => $token,
            'user'    => $this->formatUser($user),
        ]);
    }

    /**
     * Mengeluarkan pengguna yang sedang login dengan menghapus semua token aktif.
     * Memerlukan autentikasi (token valid) untuk mengakses endpoint ini.
     *
     * @param Request $request - HTTP request dengan token autentikasi di header
     * @return JsonResponse - Pesan sukses setelah logout
     */
    public function logout(Request $request): JsonResponse
    {
        // Hapus semua token milik user yang sedang login
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Mengembalikan data profil pengguna yang sedang terautentikasi.
     * Digunakan untuk memverifikasi token dan mendapatkan data user terkini.
     *
     * @param Request $request - HTTP request dengan token autentikasi di header
     * @return JsonResponse - Data lengkap user yang sedang login
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user'    => $this->formatUser($request->user()),
        ]);
    }

    /**
     * Memformat data user menjadi array yang konsisten untuk respons API.
     * Menyertakan URL foto profil dan menyembunyikan data sensitif.
     *
     * @param User $user - Instance model User
     * @return array - Array data user yang sudah diformat
     */
    private function formatUser(User $user): array
    {
        return [
            'id'                => $user->id,
            'name'              => $user->name,
            'username'          => $user->username,
            'email'             => $user->email,
            'bio'               => $user->bio,
            'profile_photo_url' => $user->profile_photo_url,
            'created_at'        => $user->created_at,
        ];
    }
}
