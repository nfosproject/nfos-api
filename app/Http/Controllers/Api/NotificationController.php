<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $audience = $request->string('audience')->toString() ?: 'customer';
        $limit = max(1, min((int) $request->integer('limit', 50), 100));

        $query = Notification::query()->forAudience($audience)->latest();

        if ($userId = $request->string('user_id')->toString()) {
            $query->where(function ($builder) use ($userId) {
                $builder->whereNull('user_id')->orWhere('user_id', $userId);
            });
        } else {
            $query->whereNull('user_id');
        }

        if ($request->boolean('only_unread')) {
            $query->unread();
        }

        $notifications = $query->take($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $notifications->map(fn (Notification $notification) => $this->formatNotification($notification)),
        ]);
    }

    public function markRead(Request $request, Notification $notification)
    {
        $data = $request->validate([
            'read' => ['nullable', 'boolean'],
        ]);

        $shouldMarkRead = array_key_exists('read', $data) ? (bool) $data['read'] : true;

        $notification->update([
            'read_at' => $shouldMarkRead ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatNotification($notification),
        ]);
    }

    public function markAllRead(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['uuid', Rule::exists('notifications', 'id')],
        ]);

        Notification::query()->whereIn('id', $validated['ids'])->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as read.',
        ]);
    }

    public function destroy(Request $request, Notification $notification)
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Authentication required.');
        }

        if ($notification->user_id && $notification->user_id !== $user->id) {
            abort(403, 'You are not allowed to delete this notification.');
        }

        if (! $notification->user_id) {
            abort(403, 'Global notifications cannot be deleted.');
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification removed.',
        ]);
    }

    protected function formatNotification(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'user_id' => $notification->user_id,
            'audience' => $notification->audience,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'link' => $notification->link,
            'metadata' => $notification->metadata,
            'read_at' => optional($notification->read_at)?->toIso8601String(),
            'created_at' => optional($notification->created_at)?->toIso8601String(),
        ];
    }
}

