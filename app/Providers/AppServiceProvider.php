<?php

declare(strict_types=1);

namespace App\Providers;

use App\Outbound\ChannelOutboundConsumer;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ChannelOutboundConsumer::class,
            ]);
        }
    }
}
