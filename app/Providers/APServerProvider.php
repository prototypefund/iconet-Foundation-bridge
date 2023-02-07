<?php

namespace App\Providers;

use ActivityPhp\Server;
use Illuminate\Support\ServiceProvider;

class APServerProvider extends ServiceProvider
{

    private Server $server;

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $mastodon = [
            'Person|Group' => ['uuid', 'publicKey', 'featured', 'featuredTags',
                'manuallyApprovesFollowers', 'discoverable', 'devices', ],
            'Note'  => ['https://iconet-foundation.org/ns#iconet',]
        ];
        Server::server([
            'instance' => ['debug' => env('DEBUG_DISABLE_HTTPS', false)],
            'logger'   => [
                'stream' => '../storage/logs/server.log'
            ],
            'dialects' => [$mastodon]
        ]);
        $this->server = Server::server();
        $this->app->singleton("server", function ($app) {
            return $this->server;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Resolving actors from uri does not initialize WebFinger, so we do it here
        Server\Http\WebFingerFactory::setServer(Server::server());
    }


}
