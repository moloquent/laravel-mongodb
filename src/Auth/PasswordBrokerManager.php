<?php

namespace Moloquent\Auth;

use Illuminate\Auth\Passwords\PasswordBrokerManager as BasePasswordBrokerManager;

class PasswordBrokerManager extends BasePasswordBrokerManager
{
    /**
     * Create a token repository instance based on the given configuration.
     *
     * @param array $config
     *
     * @return \Illuminate\Auth\Passwords\TokenRepositoryInterface
     */
    protected function createTokenRepository(array $config)
    {
        $laravel = app();

        if (starts_with($laravel::VERSION, '5.4')) {
            return new DatabaseTokenRepository(
                $this->app['db']->connection(),
                $this->app['hash'],
                $config['table'],
                $this->app['config']['app.key'],
                $config['expire']
            );
        } else {
            return new DatabaseTokenRepository(
                $this->app['db']->connection(),
                $config['table'],
                $this->app['config']['app.key'],
                $config['expire']
            );
        }
    }
}
