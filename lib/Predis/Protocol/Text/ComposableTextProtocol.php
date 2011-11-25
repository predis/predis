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

use Predis\Commands\ICommand;
use Predis\Protocol\IResponseReader;
use Predis\Protocol\ICommandSerializer;
use Predis\Protocol\IComposableProtocolProcessor;
use Predis\Network\IConnectionComposable;

/**
 * Implements a customizable protocol processor that uses the standard Redis
 * wire protocol to serialize Redis commands and parse replies returned by
 * the server using a pluggable set of classes.
 *
 * @link http://redis.io/topics/protocol
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ComposableTextProtocol implements IComposableProtocolProcessor
{
    private $serializer;
    private $reader;

    /**
     * @param array $options Set of options used to initialize the protocol processor.
     */
    public function __construct(Array $options = array())
    {
        $this->setSerializer(new TextCommandSerializer());
        $this->setReader(new TextResponseReader());

        if (count($options) > 0) {
            $this->initializeOptions($options);
        }
    }

    /**
     * Initializes the protocol processor using a set of options.
     *
     * @param array $options Set of options.
     */
    private function initializeOptions(Array $options)
    {
        foreach ($options as $k => $v) {
            $this->setOption($k, $v);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($option, $value)
    {
        switch ($option) {
            case 'iterable_multibulk':
                $handler = $value ? new ResponseMultiBulkStreamHandler() : new ResponseMultiBulkHandler();
                $this->reader->setHandler(TextProtocol::PREFIX_MULTI_BULK, $handler);
                break;

            case 'throw_errors':
                $handler = $value ? new ResponseErrorHandler() : new ResponseErrorSilentHandler();
                $this->reader->setHandler(TextProtocol::PREFIX_ERROR, $handler);
                break;

            default:
                throw new \InvalidArgumentException("The option $option is not supported by the current protocol");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(ICommand $command)
    {
        return $this->serializer->serialize($command);
    }

    /**
     * {@inheritdoc}
     */
    public function write(IConnectionComposable $connection, ICommand $command)
    {
        $connection->writeBytes($this->serializer->serialize($command));
    }

    /**
     * {@inheritdoc}
     */
    public function read(IConnectionComposable $connection)
    {
        return $this->reader->read($connection);
    }

    /**
     * {@inheritdoc}
     */
    public function setSerializer(ICommandSerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function setReader(IResponseReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function getReader()
    {
        return $this->reader;
    }
}
