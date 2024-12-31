<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use MongoDB\Client as MongoClient;

class MongoDBServiceProvider extends ServiceProvider
{
    /**
     * Enregistrer les services dans le conteneur.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('mongodb', function ($app) {
            $uri = env('DB_URI', 'mongodb://127.0.0.1:27017');
            return new MongoClient($uri);
        });
    }

    /**
     * Bootstrap les services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
