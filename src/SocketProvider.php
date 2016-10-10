<?php
// Created by dealloc. All rights reserved.

namespace Experus\Sockets;

use Experus\Sockets\Commands\GenerateControllerCommand;
use Experus\Sockets\Commands\GenerateMiddlewareCommand;
use Experus\Sockets\Commands\ServeCommand;
use Experus\Sockets\Contracts\Routing\Router;
use Experus\Sockets\Contracts\Server\Server;
use Experus\Sockets\Core\Client\SocketClient;
use Experus\Sockets\Core\Protocols\ExperusProtocol;
use Experus\Sockets\Core\Routing\SocketRouter;
use Experus\Sockets\Core\Server\SocketServer;
use Illuminate\Support\ServiceProvider;

/**
 * Class SocketProvider installs the contracts from the socket package and publishes the required config files.
 * @package Experus\Sockets
 */
class SocketProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    public $defer = true;

    /**
     * The global middleware stack.
     *
     * @var array
     */
    public $middlewares = [];

    /**
     * The supplied protocol stack.
     *
     * @var array
     */
    public $protocols = [
        SocketClient::DEFAULT_PROTOCOL => ExperusProtocol::class,
    ];

    /**
     * The bindings this service provider provides.
     *
     * @var array
     */
    private $bindings = [
        Server::class => SocketServer::class,
        Router::class => SocketRouter::class,
    ];

    /**
     * Register the services.
     */
    public function register()
    {
        foreach ($this->bindings as $contract => $binding) {
            $this->app->singleton($contract, $binding);
        }

        $this->commands([
            ServeCommand::class,
            GenerateControllerCommand::class,
            GenerateMiddlewareCommand::class,
        ]);

        $this->registerMiddleware();
        $this->registerProtocols();
    }

    /**
     * Expose the configuration exports.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../files/socket' => base_path('socket'),
            __DIR__ . '/../files/routes.php' => base_path('routes/socket.php'),
        ], 'socket');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_keys($this->bindings);
    }

    /**
     * Register all middlewares
     */
    private function registerMiddleware()
    {
        if (!empty($this->middlewares)) {
            $server = $this->app->make(Server::class);
            foreach ($this->middlewares as $middleware) {
                $server->registerMiddleware($middleware);
            }
        }
    }

    private function registerProtocols()
    {
        $server = $this->app->make(Server::class);

        if (!empty($this->protocols)) {
            foreach ($this->protocols as $name => $protocol) {
                $server->registerProtocol($name, $protocol);
            }
        }
    }
}