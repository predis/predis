<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use InvalidArgumentException;
use Predis\Command\RawCommand;
use Predis\NotSupportedException;
use Relay\Relay;

class RelayFactory extends Factory
{
    /**
     * @var string[]
     */
    protected $schemes = [
        'tcp' => RelayConnection::class,
        'tls' => RelayConnection::class,
        'unix' => RelayConnection::class,
        'redis' => RelayConnection::class,
        'rediss' => RelayConnection::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function define($scheme, $initializer)
    {
        throw new NotSupportedException('Does not allow to override existing initializer.');
    }

    /**
     * {@inheritDoc}
     */
    public function undefine($scheme)
    {
        throw new NotSupportedException('Does not allow to override existing initializer.');
    }

    /**
     * {@inheritDoc}
     */
    public function create($parameters): NodeConnectionInterface
    {
        $this->assertExtensions();

        if (!$parameters instanceof ParametersInterface) {
            $parameters = $this->createParameters($parameters);
        }

        $scheme = $parameters->scheme;

        if (!isset($this->schemes[$scheme])) {
            throw new InvalidArgumentException("Unknown connection scheme: '$scheme'.");
        }

        $initializer = $this->schemes[$scheme];
        $client = $this->createClient();

        $connection = new $initializer($parameters, $client);

        $this->prepareConnection($connection);

        return $connection;
    }

    /**
     * Checks if the Relay extension is loaded in PHP.
     */
    private function assertExtensions()
    {
        if (!extension_loaded('relay')) {
            throw new NotSupportedException(
                'The "relay" extension is required by this connection backend.'
            );
        }
    }

    /**
     * Creates a new instance of the client.
     *
     * @return Relay
     */
    private function createClient()
    {
        $client = new Relay();

        // throw when errors occur and return `null` for non-existent keys
        $client->setOption(Relay::OPT_PHPREDIS_COMPATIBILITY, false);

        // use reply literals
        $client->setOption(Relay::OPT_REPLY_LITERAL, true);

        // disable Relay's command/connection retry
        $client->setOption(Relay::OPT_MAX_RETRIES, 0);

        // whether to use in-memory caching
        $client->setOption(Relay::OPT_USE_CACHE, $this->parameters->cache ?? true);

        // set data serializer
        $client->setOption(Relay::OPT_SERIALIZER, constant(sprintf(
            '%s::SERIALIZER_%s',
            Relay::class,
            strtoupper($this->parameters->serializer ?? 'none')
        )));

        // set data compression algorithm
        $client->setOption(Relay::OPT_COMPRESSION, constant(sprintf(
            '%s::COMPRESSION_%s',
            Relay::class,
            strtoupper($this->parameters->compression ?? 'none')
        )));

        return $client;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareConnection(NodeConnectionInterface $connection)
    {
        $parameters = $connection->getParameters();

        if (isset($parameters->password) && strlen($parameters->password)) {
            $cmdAuthArgs = isset($parameters->username) && strlen($parameters->username)
                ? [$parameters->username, $parameters->password]
                : [$parameters->password];

            $connection->addConnectCommand(
                new RawCommand('AUTH', $cmdAuthArgs)
            );
        }

        if (isset($parameters->database) && strlen($parameters->database)) {
            $connection->addConnectCommand(
                new RawCommand('SELECT', [$parameters->database])
            );
        }
    }
}
