<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Environment values that must win over the host shell environment.
     *
     * The host process can export values such as APP_ENV=local, and PHPUnit's
     * `<env>` directive only writes $_ENV/$_SERVER when the key is unset. Forcing
     * both here keeps the suite hermetic regardless of the developer's shell.
     *
     * @var array<string, string>
     */
    private const FORCED_ENV = [
        'APP_ENV' => 'testing',
        'APP_MAINTENANCE_DRIVER' => 'file',
        'BROADCAST_CONNECTION' => 'null',
        'CACHE_STORE' => 'array',
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => ':memory:',
        'MAIL_MAILER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'SESSION_DRIVER' => 'array',
    ];

    protected function setUp(): void
    {
        foreach (self::FORCED_ENV as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        parent::setUp();
    }
}
