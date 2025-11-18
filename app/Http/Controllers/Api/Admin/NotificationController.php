<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    private const TYPES = ['order_status', 'product_approval', 'low_stock', 'marketing', 'general'];
    private const AUDIENCES = ['admin', 'customer', 'seller', 'all'];

    public function index(Request $request)
    {
        $limit = max(1, min((int) $request->integer('limit', 50), 100));
        $audience = $request->string('audience')->toString();

        $query = Notification::query()->latest();

        if ($audience) {
            $query->forAudience($audience);
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

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $notification = Notification::create($data);

        return response()->json([
            'success' => true,
            'data' => $this->formatNotification($notification),
        ], 201);
    }

    public function update(Request $request, Notification $notification)
    {
        $data = $this->validatedData($request, $notification);

        $notification->update($data);

        return response()->json([
            'success' => true,
            'data' => $this->formatNotification($notification->fresh()),
        ]);
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted.',
        ]);
    }

    public function markAllRead(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['uuid', Rule::exists('notifications', 'id')],
        ]);

        Notification::query()->whereIn('id', $validated['ids'])->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as read.',
        ]);
    }

    protected function validatedData(Request $request, ?Notification $notification = null): array
    {
        $validated = $request->validate([
            'title' => [$notification ? 'sometimes' : 'required', 'string', 'max:255'],
            'message' => [$notification ? 'sometimes' : 'required', 'string'],
            'type' => [$notification ? 'sometimes' : 'required', Rule::in(self::TYPES)],
            'audience' => [$notification ? 'sometimes' : 'required', Rule::in(self::AUDIENCES)],
            'link' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'read' => ['sometimes', 'boolean'],
        ]);

        $data = array_merge($notification?->toArray() ?? [], $validated);

        if (array_key_exists('metadata', $validated)) {
            $data['metadata'] = $validated['metadata'];
        }

        if (array_key_exists('read', $validated)) {
            $data['read_at'] = $validated['read'] ? now() : null;
        }

        return $data;
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

