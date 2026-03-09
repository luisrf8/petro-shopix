<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $notifications = $user
            ? $user->notifications()->latest()->paginate(20)
            : collect();

        return view('notifications', compact('notifications'));
    }

    public function markAsRead(string $id): RedirectResponse
    {
        $user = auth()->user();

        if ($user) {
            $notification = $user->notifications()->where('id', $id)->first();
            if ($notification && is_null($notification->read_at)) {
                $notification->markAsRead();
            }
        }

        return back();
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $notifications = $user->notifications()
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Notificación',
                    'message' => $notification->data['message'] ?? '',
                    'type' => $notification->data['type'] ?? 'info',
                    'order_id' => $notification->data['order_id'] ?? null,
                    'payment_id' => $notification->data['payment_id'] ?? null,
                    'created_at' => optional($notification->created_at)->toDateTimeString(),
                    'read_at' => optional($notification->read_at)->toDateTimeString(),
                    'is_read' => !is_null($notification->read_at),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    public function apiMarkAsRead(string $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function webFeed(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        $notifications = $user->notifications()
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Notificación',
                    'message' => $notification->data['message'] ?? '',
                    'type' => $notification->data['type'] ?? 'info',
                    'order_id' => $notification->data['order_id'] ?? null,
                    'payment_id' => $notification->data['payment_id'] ?? null,
                    'created_at' => optional($notification->created_at)->toDateTimeString(),
                    'read_at' => optional($notification->read_at)->toDateTimeString(),
                    'is_read' => !is_null($notification->read_at),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }
}
