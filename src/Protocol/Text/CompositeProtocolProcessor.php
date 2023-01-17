<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Protocol\Text;

use Predis\Command\CommandInterface;
use Predis\Connection\CompositeConnectionInterface;
use Predis\Protocol\ProtocolProcessorInterface;
use Predis\Protocol\RequestSerializerInterface;
use Predis\Protocol\ResponseReaderInterface;

/**
 * Composite protocol processor for the standard Redis wire protocol using
 * pluggable handlers to serialize requests and deserialize responses.
 *
 * @see http://redis.io/topics/protocol
 */
class CompositeProtocolProcessor implements ProtocolProcessorInterface
{
    /*
     * @var RequestSerializerInterface
     */
    protected $serializer;

    /*
     * @var ResponseReaderInterface
     */
    protected $reader;

    /**
     * @param RequestSerializerInterface $serializer Request serializer.
     * @param ResponseReaderInterface    $reader     Response reader.
     */
    public function __construct(
        RequestSerializerInterface $serializer = null,
        ResponseReaderInterface $reader = null
    ) {
        $this->setRequestSerializer($serializer ?: new RequestSerializer());
        $this->setResponseReader($reader ?: new ResponseReader());
    }

    /**
     * {@inheritdoc}
     */
    public function write(CompositeConnectionInterface $connection, CommandInterface $command)
    {
        $connection->writeBuffer($this->serializer->serialize($command));
    }

    /**
     * {@inheritdoc}
     */
    public function read(CompositeConnectionInterface $connection)
    {
        return $this->reader->read($connection);
    }

    /**
     * Sets the request serializer used by the protocol processor.
     *
     * @param RequestSerializerInterface $serializer Request serializer.
     */
    public function setRequestSerializer(RequestSerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Returns the request serializer used by the protocol processor.
     *
     * @return RequestSerializerInterface
     */
    public function getRequestSerializer()
    {
        return $this->serializer;
    }

    /**
     * Sets the response reader used by the protocol processor.
     *
     * @param ResponseReaderInterface $reader Response reader.
     */
    public function setResponseReader(ResponseReaderInterface $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Returns the Response reader used by the protocol processor.
     *
     * @return ResponseReaderInterface
     */
    public function getResponseReader()
    {
        return $this->reader;
    }
}
