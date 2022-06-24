<?php


namespace Tomorrow\FridayFriendship;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;


class FridayFriendshipProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerMigrations();
        Factory::guessFactoryNamesUsing(function (string $modelName) {
            return 'Database\\Factories\\'.class_basename($modelName).'Factory';
        });
        // $this->registerEloquentFactoriesFrom(__DIR__.'/../database/factories');
        // $this->registerSeedsFrom(__DIR__.'/../database/seeds');
    }

    /**
     * Register FridayFriendship's migration files.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        $config = $this->app['config']['FridayFriendship'];
        $runMigrations = is_null($config['migrations'] ?? null) 
            ? count(\File::glob(database_path('migrations/*FridayFriendship*.php'))) === 0
            : $config['migrations'];

        if ($runMigrations) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->configure();
        $this->offerPublishing();
    }

    /**
     * Setup the configuration for FridayFriendship.
     *
     * @return void
     */
    protected function configure()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/FridayFriendship.php', 'FridayFriendship'
        );
    }

    /**
     * Setup the resource publishing groups for FridayFriendship.
     *
     * @return void
     */
    protected function offerPublishing()
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__.'/../config/FridayFriendship.php' => config_path('FridayFriendship.php'),
            ], 'FridayFriendship-config');

            $this->publishes($this->updateMigrationDate(), 'FridayFriendship-migrations');
        }
    }


    /**
     * Returns existing migration file if found, else uses the current timestamp.
     *
     * @return array
     */
    protected function updateMigrationDate(): array
    {
        $tempArray = [];
        $path = __DIR__.'/../database/migrations';
        foreach (\File::allFiles($path) as $file) {
            $tempArray[$path.'/'.\File::basename($file)] = app()->databasePath()."/migrations/".date('Y_m_d_His').'_'.\File::basename($file);
        }

        return $tempArray;
    }
}
