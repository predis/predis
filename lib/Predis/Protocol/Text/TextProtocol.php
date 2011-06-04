<?php

namespace Predis\Protocol\Text;

use Predis\Helpers;
use Predis\ResponseError;
use Predis\ResponseQueued;
use Predis\ServerException;
use Predis\Commands\ICommand;
use Predis\Protocol\IProtocolProcessor;
use Predis\Protocol\ProtocolException;
use Predis\Network\IConnectionComposable;
use Predis\Iterators\MultiBulkResponseSimple;

class TextProtocol implements IProtocolProcessor {
    const NEWLINE = "\r\n";
    const OK      = 'OK';
    const ERROR   = 'ERR';
    const QUEUED  = 'QUEUED';
    const NULL    = 'nil';

    const PREFIX_STATUS     = '+';
    const PREFIX_ERROR      = '-';
    const PREFIX_INTEGER    = ':';
    const PREFIX_BULK       = '$';
    const PREFIX_MULTI_BULK = '*';

    const BUFFER_SIZE = 4096;

    private $_mbiterable, $_throwErrors, $_serializer;

    public function __construct() {
        $this->_mbiterable  = false;
        $this->_throwErrors = true;
        $this->_serializer  = new TextCommandSerializer();
    }

    public function write(IConnectionComposable $connection, ICommand $command) {
        $connection->writeBytes($this->_serializer->serialize($command));
    }

    public function read(IConnectionComposable $connection) {
        $chunk = $connection->readLine();
        $prefix = $chunk[0];
        $payload = substr($chunk, 1);
        switch ($prefix) {
            case '+':    // inline
                switch ($payload) {
                    case 'OK':
                        return true;
                    case 'QUEUED':
                        return new ResponseQueued();
                    default:
                        return $payload;
                }

            case '$':    // bulk
                $size = (int) $payload;
                if ($size === -1) {
                    return null;
                }
                return substr($connection->readBytes($size + 2), 0, -2);

            case '*':    // multi bulk
                $count = (int) $payload;
                if ($count === -1) {
                    return null;
                }
                if ($this->_mbiterable == true) {
                    return new MultiBulkResponseSimple($connection, $count);
                }
                $multibulk = array();
                for ($i = 0; $i < $count; $i++) {
                    $multibulk[$i] = $this->read($connection);
                }
                return $multibulk;

            case ':':    // integer
                return (int) $payload;

            case '-':    // error
                if ($this->_throwErrors) {
                    throw new ServerException($payload);
                }
                return new ResponseError($payload);

            default:
                Helpers::onCommunicationException(new ProtocolException(
                    $connection, "Unknown prefix: '$prefix'"
                ));
        }
    }

    public function setOption($option, $value) {
        switch ($option) {
            case 'iterable_multibulk':
                $this->_mbiterable = (bool) $value;
                break;
            case 'throw_errors':
                $this->_throwErrors = (bool) $value;
                break;
        }
    }
}
