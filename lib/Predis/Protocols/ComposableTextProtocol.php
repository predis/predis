<?php

namespace Predis\Protocols;

use Predis\Commands\ICommand;
use Predis\Network\IConnectionComposable;

class ComposableTextProtocol implements IProtocolProcessorExtended {
    private $_serializer, $_reader;

    public function __construct(Array $options = array()) {
        $this->setSerializer(new TextCommandSerializer());
        $this->setReader(new TextResponseReader());
        if (count($options) > 0) {
            $this->initializeOptions($options);
        }
    }

    private function initializeOptions(Array $options) {
        foreach ($options as $k => $v) {
            $this->setOption($k, $v);
        }
    }

    public function setOption($option, $value) {
        switch ($option) {
            case 'iterable_multibulk':
                $handler = $value ? new ResponseMultiBulkStreamHandler() : new ResponseMultiBulkHandler();
                $this->_reader->setHandler(TextProtocol::PREFIX_MULTI_BULK, $handler);
                break;
            case 'throw_errors':
                $handler = $value ? new ResponseErrorHandler() : new ResponseErrorSilentHandler();
                $this->_reader->setHandler(TextProtocol::PREFIX_ERROR, $handler);
                break;
            default:
                throw new \InvalidArgumentException(
                    "The option $option is not supported by the current protocol"
                );
        }
    }

    public function serialize(ICommand $command) {
        return $this->_serializer->serialize($command);
    }

    public function write(IConnectionComposable $connection, ICommand $command) {
        $connection->writeBytes($this->_serializer->serialize($command));
    }

    public function read(IConnectionComposable $connection) {
        return $this->_reader->read($connection);
    }

    public function setSerializer(ICommandSerializer $serializer) {
        $this->_serializer = $serializer;
    }

    public function getSerializer() {
        return $this->_serializer;
    }

    public function setReader(IResponseReader $reader) {
        $this->_reader = $reader;
    }

    public function getReader() {
        return $this->_reader;
    }
}
