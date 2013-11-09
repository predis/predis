<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Protocol\Text;

use Predis\CommunicationException;
use Predis\Connection\ComposableConnectionInterface;
use Predis\Protocol\ProtocolException;
use Predis\Protocol\ResponseHandlerInterface;
use Predis\Protocol\ResponseReaderInterface;

/**
 * Response reader for the standard Redis wire protocol.
 *
 * @link http://redis.io/topics/protocol
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class TextResponseReader implements ResponseReaderInterface
{
    protected $handlers;

    /**
     *
     */
    public function __construct()
    {
        $this->handlers = $this->getDefaultHandlers();
    }

    /**
     * Returns the default handlers for the supported type of responses.
     *
     * @return array
     */
    protected function getDefaultHandlers()
    {
        return array(
            TextProtocol::PREFIX_STATUS     => new ResponseStatusHandler(),
            TextProtocol::PREFIX_ERROR      => new ResponseErrorHandler(),
            TextProtocol::PREFIX_INTEGER    => new ResponseIntegerHandler(),
            TextProtocol::PREFIX_BULK       => new ResponseBulkHandler(),
            TextProtocol::PREFIX_MULTI_BULK => new ResponseMultiBulkHandler(),
        );
    }

    /**
     * Sets a response handler for a certain prefix that identifies a type of
     * response that can be returned by Redis.
     *
     * @param string $prefix Identifier of the type of response.
     * @param ResponseHandlerInterface $handler Response handler.
     */
    public function setHandler($prefix, ResponseHandlerInterface $handler)
    {
        $this->handlers[$prefix] = $handler;
    }

    /**
     * Returns the response handler associated to a certain type of response.
     *
     * @param string $prefix Identifier of the type of response.
     * @return ResponseHandlerInterface
     */
    public function getHandler($prefix)
    {
        if (isset($this->handlers[$prefix])) {
            return $this->handlers[$prefix];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function read(ComposableConnectionInterface $connection)
    {
        $header = $connection->readLine();

        if ($header === '') {
            $this->onProtocolError($connection, 'Unexpected empty header');
        }

        $prefix = $header[0];

        if (!isset($this->handlers[$prefix])) {
            $this->onProtocolError($connection, "Unknown prefix: '$prefix'");
        }

        $handler = $this->handlers[$prefix];

        return $handler->handle($connection, substr($header, 1));
    }

    /**
     * Handles protocol errors generated while reading responses from the
     * connection.
     *
     * @param ComposableConnectionInterface $connection Connection to Redis that generated the error.
     * @param string $message Error message.
     */
    protected function onProtocolError(ComposableConnectionInterface $connection, $message)
    {
        CommunicationException::handle(
            new ProtocolException($connection, $message)
        );
    }
}
