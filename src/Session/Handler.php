<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Session;

use Predis\ClientInterface;
use Predis\Response\ErrorInterface;
use Predis\Response\ServerException;
use ReturnTypeWillChange;
use SessionHandlerInterface;
use SessionIdInterface;
use SessionUpdateTimestampHandlerInterface;

/**
 * Session handler class that relies on Predis\Client to store PHP's sessions
 * data into one or multiple Redis servers.
 */
class Handler implements SessionHandlerInterface, SessionIdInterface, SessionUpdateTimestampHandlerInterface
{
    protected $client;
    protected $ttl;

    /**
     * @param ClientInterface $client  Fully initialized client instance.
     * @param array           $options Session handler options.
     */
    public function __construct(ClientInterface $client, array $options = [])
    {
        $this->client = $client;

        if (isset($options['gc_maxlifetime'])) {
            $this->ttl = (int) $options['gc_maxlifetime'];
        } else {
            $this->ttl = ini_get('session.gc_maxlifetime');
        }
    }

    /**
     * Registers this instance as the current session handler.
     */
    public function register()
    {
        session_set_save_handler($this, true);
    }

    #[ReturnTypeWillChange]
    public function create_sid()
    {
        do {
            $id = bin2hex(random_bytes(32));
        } while ($this->validateId($id));

        return $id;
    }

    #[ReturnTypeWillChange]
    public function validateId($id)
    {
        $response = $this->client->exists($id);

        if ($response instanceof ErrorInterface) {
            throw new ServerException($response->getMessage());
        }

        return (bool) $response;
    }

    #[ReturnTypeWillChange]
    public function updateTimestamp($id, $data)
    {
        return $this->write($id, $data);
    }

    /**
     * @param  string $save_path
     * @param  string $session_id
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function open($save_path, $session_id)
    {
        // NOOP
        return true;
    }

    /**
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function close()
    {
        // NOOP
        return true;
    }

    /**
     * @param  int  $maxlifetime
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function gc($maxlifetime)
    {
        // NOOP
        return true;
    }

    /**
     * @param  string $session_id
     * @return string
     */
    #[ReturnTypeWillChange]
    public function read($session_id)
    {
        if ($data = $this->client->get($session_id)) {
            return $data;
        }

        return '';
    }

    /**
     * @param  string $session_id
     * @param  string $session_data
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function write($session_id, $session_data)
    {
        $this->client->setex($session_id, $this->ttl, $session_data);

        return true;
    }

    /**
     * @param  string $session_id
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function destroy($session_id)
    {
        $this->client->del($session_id);

        return true;
    }

    /**
     * Returns the underlying client instance.
     *
     * @return ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Returns the session max lifetime value.
     *
     * @return int
     */
    public function getMaxLifeTime()
    {
        return $this->ttl;
    }
}
