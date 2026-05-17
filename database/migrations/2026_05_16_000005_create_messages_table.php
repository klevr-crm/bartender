<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations');
            $table->string('direction'); // inbound | outbound
            $table->string('role'); // user | assistant | system
            $table->text('content')->nullable();
            $table->json('media')->nullable();
            $table->string('external_message_id')->nullable();
            $table->string('status')->default('pending'); // pending | sent | delivered | read | failed
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
