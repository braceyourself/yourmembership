<?php namespace Braceyourself\Yourmembership\Tests;

use Braceyourself\Yourmembership\YourmembershipServiceProvider;
use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $loadEnvironmentVariables = true;

    protected function getPackageProviders($app)
    {
        return [
            DatabaseServiceProvider::class,
            FilesystemServiceProvider::class,
            CacheServiceProvider::class,
            YourmembershipServiceProvider::class,
        ];
    }

    protected function getBasePath()
    {
        return __DIR__ . '/../';
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set([
            'database.connections.testing' => [
                'driver'   => 'sqlite',
                'path'     => 'database.sqlite',
                'database' => 'database',
            ]
        ]);

        /*
        $migration = include __DIR__.'/../database/migrations/create_skeleton_table.php.stub';
        $migration->up();
        */
    }
}
