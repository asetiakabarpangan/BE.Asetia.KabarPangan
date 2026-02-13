<?php

namespace App\Http\Controllers;

use Illuminate\Http\{Request, JsonResponse};

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->latest()->paginate(10);
        return new JsonResponse([
            'success' => true,
            'data' => $notifications
        ]);
    }

    public function markAsRead(Request $request, $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }
        return new JsonResponse([
            'success' => true,
            'message' => 'Ditandai sudah dibaca'
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();
        return new JsonResponse([
            'success' => true,
            'message' => 'Semua ditandai sudah dibaca'
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();
        return new JsonResponse([
            'success' => true,
            'data'    => [
                'unread_count' => $count
            ]
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->delete();
            return new JsonResponse([
                'success' => true,
                'message' => 'Notifikasi berhasil dihapus'
            ]);
        }
        return new JsonResponse([
            'success' => false,
            'message' => 'Notifikasi tidak ditemukan'
        ], 404);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();
        return new JsonResponse([
            'success' => true,
            'message' => 'Seluruh riwayat notifikasi berhasil dihapus'
        ]);
    }
}
