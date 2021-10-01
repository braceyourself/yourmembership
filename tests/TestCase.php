<?php namespace VendorName\Skeleton\Tests;

use Braceyourself\Yourmembership\YourmembershipServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\CreatesApplication;
use VendorName\Skeleton\SkeletonServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Braceyourself\\Yourmembership\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            YourmembershipServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');


        config([
            'yourmembership.accounts' => [
                "account" => [
                    'api_version'    => null,
                    'api_key'        => env('PUBLIC_KEY'),
                    'private_key'    => env('PRIVATE_KEY'),
                    'sa_passcode'    => env('SA_PASSCODE'),
                    'usermeta_class' => null,
                    'user_class'     => null,
                ]
            ]
        ]);
        /*
        $migration = include __DIR__.'/../database/migrations/create_skeleton_table.php.stub';
        $migration->up();
        */
    }
}
