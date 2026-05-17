<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('scenario_id')->constrained('scenarios');
            $table->foreignId('persona_id')->constrained('personas');
            $table->foreignId('channel_instance_id')->constrained('channel_instances');
            $table->string('external_conversation_id')->nullable();
            $table->string('status')->default('active');
            $table->integer('turn_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
