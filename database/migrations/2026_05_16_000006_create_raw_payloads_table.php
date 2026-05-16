<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_payloads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations');
            $table->string('direction'); // inbound | outbound
            $table->string('channel');
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_payloads');
    }
};
