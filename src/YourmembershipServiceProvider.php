<?php namespace Braceyourself\Yourmembership;

use Braceyourself\Yourmembership\Clients\Client;
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
            $client = $this->getClientForVersion(data_get($config, 'api_version'));

            $concrete = fn() => new $client($config, $k);

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

    private function getClientForVersion($version)
    {
        $client_class = __NAMESPACE__ . "\\Clients\\V{$version}\\Client";

        return class_exists($client_class)
            ? $client_class
            : Client::class;
    }
}
