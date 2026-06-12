<?php

namespace App\Http\Controllers;

use App\Jobs\SendNotification;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function broadcast(Request $request)
    {
        $data = $request->validate([
            'channel' => 'required|in:sms,email',
            'message' => 'required|string',
            'recipient_ids' => 'required|array|min:1',
            'recipient_ids.*' => 'string',
            'priority' => 'sometimes|in:transactional,marketing',
            'idempotency_key' => 'sometimes|string|max:255',
        ]);

        $priority = $data['priority'] ?? 'marketing';
        $idempotencyKey = $data['idempotency_key'] ?? null;
        
        $notificationIds = [];
        
        foreach ($data['recipient_ids'] as $recipientId) {
            if ($idempotencyKey) {
                $existing = Notification::where('idempotency_key', $idempotencyKey . ':' . $recipientId)->first();
                if ($existing) {
                    continue;
                }
            }
            
            $notification = Notification::create([
                'id' => (string) Str::uuid(),
                'idempotency_key' => $idempotencyKey ? $idempotencyKey . ':' . $recipientId : null,
                'recipient_id' => $recipientId,
                'channel' => $data['channel'],
                'priority' => $priority,
                'status' => 'queued',
                'message' => $data['message'],
            ]);
            
            $job = new SendNotification($notification);
            $job->queue = $priority === 'transactional' ? 'high' : 'low';
            dispatch($job);
            
            $notificationIds[] = $notification->id;
        }
        
        if (empty($notificationIds)) {
            return response()->json(['error' => 'Duplicate request, all notifications already processed'], 409);
        }

        return response()->json([
            'batch_id' => (string) Str::uuid(),
            'notification_ids' => $notificationIds,
            'total' => count($notificationIds),
            'status' => 'accepted'
        ], 202);
    }

    public function history($subscriberId)
    {
        $notifications = Notification::where('recipient_id', $subscriberId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($notifications);
    }
}