<?php

namespace ScrapyardIO\Tubes\Fonts\Providers;

use Fabricate\NutsAndBolts\Contracts\DeferrableProvider;
use Fabricate\NutsAndBolts\ServiceProvider;
use ScrapyardIO\Tubes\Contracts\Fonts\FontFactory;
use ScrapyardIO\Tubes\Fonts\FontManager;

class FontsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/fonts.php',
            'fonts',
        );

        $this->container->singleton('font', function ($app) {
            return new FontManager(
                $app->bound('config') ? $app->make('config')->get('fonts', []) : [],
            );
        });

        $this->container->singleton(FontManager::class, fn ($app) => $app->make('font'));
        $this->container->singleton(FontFactory::class, fn ($app) => $app->make('font'));
    }

    public function boot(): void
    {
        if ($this->container->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/fonts.php' => $this->container->configPath('fonts.php'),
            ], 'tubes-fonts-config');
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'font',
            FontManager::class,
            FontFactory::class,
        ];
    }
}
