<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateDeliveryStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $notification;
    protected $providerMessageId;

    public function __construct(Notification $notification, $providerMessageId)
    {
        $this->notification = $notification;
        $this->providerMessageId = $providerMessageId;
    }

    public function handle()
    {
        $this->notification->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }
}