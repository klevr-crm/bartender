<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_instances', function (Blueprint $table): void {
            $table->id();
            $table->string('provider');
            $table->string('channel_type');
            $table->string('external_id');
            $table->string('name')->nullable();
            $table->json('config')->nullable();
            $table->timestamp('last_post_at')->nullable();
            $table->timestamp('last_mq_ping_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_instances');
    }
};
