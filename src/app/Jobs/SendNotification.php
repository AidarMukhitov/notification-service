<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\ProviderMock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public $notification;
    public $tries = 3;
    public $backoff = [5, 15, 30];
    public $queue; // <-- Добавьте это свойство

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function handle(ProviderMock $provider)
    {
        if ($this->notification->status !== 'queued') {
            return;
        }

        try {
            $result = $provider->send(
                $this->notification->channel,
                $this->notification->recipient_id,
                $this->notification->message
            );

            $this->notification->update([
                'status' => $result['status'],
                'provider_response' => $result['response'],
                'sent_at' => now(),
            ]);

            if ($result['status'] === 'sent' && isset($result['provider_message_id'])) {
                UpdateDeliveryStatus::dispatch($this->notification, $result['provider_message_id'])
                    ->delay(now()->addSeconds(3));
            }
        } catch (\Exception $e) {
            Log::error("Send failed: " . $e->getMessage());
            $this->notification->increment('retry_count');
            
            if ($this->attempts() >= $this->tries) {
                $this->notification->update([
                    'status' => 'bounced',
                    'provider_response' => 'Max retries exceeded'
                ]);
            } else {
                $this->release($this->backoff[$this->attempts() - 1] ?? 30);
            }
            
            throw $e;
        }
    }
}