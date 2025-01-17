<?php

namespace Eduardokum\LaravelMailAutoEmbed;

use Eduardokum\LaravelMailAutoEmbed\Listeners\SwiftEmbedImages;
use Eduardokum\LaravelMailAutoEmbed\Listeners\SymfonyEmbedImages;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Throwable;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([$this->getConfigPath() => config_path('mail-auto-embed.php')], 'config');
        if (version_compare(app()->version(), '9.0.0', '>=')) {
            Event::listen(function (MessageSending $event) {
                (new SymfonyEmbedImages($this->app['config']->get('mail-auto-embed')))
                    ->handle($event);
            });
        } else {
            foreach (Arr::get($this->app['config'], 'mail.mailers', []) as $driver => $mailer) {
                try {
                    // If transport not exists this will throw an exception
                    Mail::driver($driver)->getSwiftMailer()->registerPlugin(new SwiftEmbedImages($this->app['config']->get('mail-auto-embed')));
                } catch (Throwable $e) {}
            }
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom($this->getConfigPath(), 'mail-auto-embed');
    }

    /**
     * @return string
     */
    protected function getConfigPath()
    {
        return __DIR__.'/../config/mail-auto-embed.php';
    }
}
