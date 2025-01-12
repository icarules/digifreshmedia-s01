<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Solarium\Client;
use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SolariumServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $adapter = new Curl();
        $adapter->setTimeout(300);
        $dispatcher = new EventDispatcher();
        $this->app->bind(Client::class, function ($app) use ($adapter, $dispatcher) {
            return new Client($adapter, $dispatcher, $app['config']['solarium']);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return string[]
     */
    public function boot()
    {
        return [Client::class];
    }
}
