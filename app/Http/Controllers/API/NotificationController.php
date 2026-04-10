<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * NotificationController — mengelola notifikasi pengguna.
 * Menyediakan daftar notifikasi, jumlah yang belum dibaca, dan tandai sudah dibaca.
 */
class NotificationController extends Controller
{
    /**
     * Mendapatkan semua notifikasi milik user yang login.
     * Diurutkan dari yang terbaru, disertai data actor dan postingan terkait.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->with([
                'actor:id,name,username,profile_photo',
                'post:id,content',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($n) => $this->formatNotification($n));

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    /**
     * Mendapatkan jumlah notifikasi yang belum dibaca.
     * Digunakan untuk menampilkan badge angka di navbar/sidebar.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['success' => true, 'count' => $count]);
    }

    /**
     * Menandai semua notifikasi sebagai sudah dibaca.
     * Dipanggil saat user membuka panel notifikasi.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'Semua notifikasi ditandai sudah dibaca.']);
    }

    /**
     * Menandai satu notifikasi sebagai sudah dibaca.
     */
    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Tidak memiliki izin.'], 403);
        }
        $notification->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }

    /**
     * Format data notifikasi untuk respons API.
     * Menghasilkan pesan notifikasi yang mudah dibaca.
     */
    private function formatNotification(Notification $n): array
    {
        $messages = [
            'like'    => 'menyukai postingan Anda',
            'repost'  => 'memposting ulang postingan Anda',
            'comment' => 'mengomentari postingan Anda',
            'follow'  => 'mulai mengikuti Anda',
        ];

        return [
            'id'         => $n->id,
            'type'       => $n->type,
            'message'    => $messages[$n->type] ?? '',
            'is_read'    => $n->is_read,
            'actor'      => $n->actor ? [
                'id'                => $n->actor->id,
                'name'              => $n->actor->name,
                'username'          => $n->actor->username,
                'profile_photo_url' => $n->actor->profile_photo_url,
            ] : null,
            'post'       => $n->post ? [
                'id'      => $n->post->id,
                'content' => mb_substr($n->post->content, 0, 60) . (mb_strlen($n->post->content) > 60 ? '...' : ''),
            ] : null,
            'created_at' => $n->created_at,
        ];
    }
}
