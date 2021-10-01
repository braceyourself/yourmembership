<?php namespace Braceyourself\Yourmembership;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;

class YourmembershipServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/yourmembership.php', 'yourmembership');

        $this->publishes([
            __DIR__ . '/../config/yourmembership.php' => config_path('yourmembership.php')
        ], 'yourmembership-config');

        foreach (config('yourmembership.accounts') as $k => $config) {

            $concrete = fn() => new YourmembershipApi($config);

            $this->app->bind("yourmembership.$k", $concrete);
            $this->app->bind("ym.$k", $concrete);
        }

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
