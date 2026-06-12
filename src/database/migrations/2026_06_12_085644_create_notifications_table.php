<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('idempotency_key')->unique()->nullable();
            $table->string('recipient_id');
            $table->string('channel');
            $table->string('priority');
            $table->enum('status', ['queued', 'sent', 'delivered', 'bounced']);
            $table->text('message');
            $table->text('provider_response')->nullable();
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            
            $table->index('recipient_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};