<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Polling ligero para alertas en tiempo real.
 * El frontend hace GET /notifications/poll cada N segundos.
 * Retorna el conteo de no-leídas y las últimas notificaciones.
 */
final class NotificationPollController extends Controller
{
    public function poll(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['unread' => 0, 'notifications' => []], 401);
        }

        $unread = $user->unreadNotifications()->count();

        $latest = $user->notifications()
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (DatabaseNotification $n) => [
                'id'       => $n->id,
                'type'     => $n->data['type'] ?? 'info',
                'title'    => $n->data['title'] ?? 'Notificación',
                'message'  => $n->data['message'] ?? '',
                'read'     => ! is_null($n->read_at),
                'time'     => $n->created_at->diffForHumans(),
                'created'  => $n->created_at->toIso8601String(),
            ]);

        return response()->json([
            'unread'        => $unread,
            'notifications' => $latest,
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['ok' => false], 401);
        }

        $id = $request->input('id');

        if ($id) {
            $user->notifications()->where('id', $id)->update(['read_at' => now()]);
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return response()->json(['ok' => true, 'unread' => $user->unreadNotifications()->count()]);
    }
}
