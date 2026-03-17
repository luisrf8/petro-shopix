<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\SalesOrder;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $notifications = $user
            ? $user->notifications()->latest()->paginate(20)->through(fn ($notification) => $this->transformNotification($notification, $user))
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

            if ($notification) {
                $targetUrl = $this->resolveNotificationTargetUrl($notification, $user);
                if ($targetUrl) {
                    return redirect($targetUrl);
                }
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
            ->map(fn ($notification) => $this->transformNotification($notification, $user))
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
            ->map(fn ($notification) => $this->transformNotification($notification, $user))
            ->values();

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    private function transformNotification($notification, $user): array
    {
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
            'target_url' => $this->resolveNotificationTargetUrl($notification, $user),
        ];
    }

    private function resolveNotificationTargetUrl($notification, $user): ?string
    {
        $orderId = $notification->data['order_id'] ?? null;
        $paymentId = $notification->data['payment_id'] ?? null;

        if ($orderId) {
            $order = SalesOrder::select('id', 'user_id', 'tenant_id')->find($orderId);
            if (!$order) {
                return null;
            }

            if ((int) $order->user_id === (int) $user->id) {
                return url('/publicOrder/' . $order->id);
            }

            if (!empty($user->tenant_id) && (int) $order->tenant_id === (int) $user->tenant_id) {
                return url('/sales/' . $order->id) . ($paymentId ? '#payment-' . $paymentId : '');
            }
        }

        return null;
    }
}
