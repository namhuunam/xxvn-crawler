<?php

namespace namhuunam\XxvnCrawler;

use Illuminate\Support\ServiceProvider;
use namhuunam\XxvnCrawler\Commands\CrawlMoviesCommand;

class XxvnCrawlerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/xxvn-crawler.php' => config_path('xxvn-crawler.php'),
        ], 'config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrawlMoviesCommand::class,
            ]);
        }

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/xxvn-crawler.php', 'xxvn-crawler'
        );

        // Register services
        $this->app->singleton('xxvn-crawler.api-service', function ($app) {
            return new Services\ApiService();
        });

        $this->app->singleton('xxvn-crawler.image-service', function ($app) {
            return new Services\ImageService();
        });

        $this->app->singleton('xxvn-crawler.movie-service', function ($app) {
            return new Services\MovieService(
                $app->make('xxvn-crawler.api-service'),
                $app->make('xxvn-crawler.image-service')
            );
        });

        $this->app->singleton('xxvn-crawler.repository', function ($app) {
            return new Repositories\MovieRepository();
        });
    }
}