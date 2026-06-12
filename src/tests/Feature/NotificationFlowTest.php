<?php

namespace Tests\Feature;

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_mass_broadcast_creates_notifications()
    {
        $payload = [
            'channel' => 'sms',
            'message' => 'Test message',
            'recipient_ids' => ['user1', 'user2', 'user3'],
            'priority' => 'transactional'
        ];

        $response = $this->postJson('/api/broadcast', $payload);
        
        $response->assertStatus(202);
        $response->assertJson(['status' => 'accepted']);
        $this->assertDatabaseCount('notifications', 3);
    }

    public function test_notification_status_flow()
    {
        $notification = Notification::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'recipient_id' => 'test_user',
            'channel' => 'sms',
            'priority' => 'transactional',
            'status' => 'queued',
            'message' => 'Test',
        ]);

        $this->assertEquals('queued', $notification->status);

        $provider = new \App\Services\ProviderMock();
        $result = $provider->send('sms', 'test_user', 'Test');
        
        $notification->update([
            'status' => $result['status'],
            'sent_at' => now(),
        ]);

        $this->assertContains($notification->status, ['sent', 'bounced']);
    }

    public function test_idempotency_prevents_duplicates()
    {
        $payload = [
            'channel' => 'email',
            'message' => 'Duplicate test',
            'recipient_ids' => ['user1'],
            'priority' => 'marketing',
            'idempotency_key' => 'unique-key-123'
        ];

        $response1 = $this->postJson('/api/broadcast', $payload);
        $response1->assertStatus(202);

        $response2 = $this->postJson('/api/broadcast', $payload);
        $response2->assertStatus(409);

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_history_endpoint_returns_notifications()
    {
        Notification::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'recipient_id' => 'history_user',
            'channel' => 'sms',
            'priority' => 'transactional',
            'status' => 'delivered',
            'message' => 'Message 1',
        ]);
        
        Notification::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'recipient_id' => 'history_user',
            'channel' => 'email',
            'priority' => 'marketing',
            'status' => 'sent',
            'message' => 'Message 2',
        ]);

        $response = $this->getJson('/api/subscribers/history_user/notifications');
        
        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }
}