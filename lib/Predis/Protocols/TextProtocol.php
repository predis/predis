<?php

namespace Predis\Protocols;

use Predis\ICommand;
use Predis\CommunicationException;
use Predis\Network\IConnectionSingle;
use Predis\Iterators\MultiBulkResponseSimple;

class TextProtocol implements IRedisProtocol {
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

    const BUFFER_SIZE = 8192;

    private $_mbiterable, $_throwErrors, $_serializer;

    public function __construct() {
        $this->_mbiterable  = false;
        $this->_throwErrors = true;
        $this->_serializer  = new TextCommandSerializer();
    }

    public function write(IConnectionSingle $connection, ICommand $command) {
        $buffer = $this->_serializer->serialize($command);
        $socket = $connection->getResource();
        while (($length = strlen($buffer)) > 0) {
            $written = fwrite($socket, $buffer);
            if ($length === $written) {
                return;
            }
            if ($written === false || $written === 0) {
                throw new CommunicationException(
                    $connection, 'Error while writing bytes to the server'
                );
            }
            $value = substr($buffer, $written);
        }
    }

    public function read(IConnectionSingle $connection) {
        $socket = $connection->getResource();
        $chunk  = fgets($socket);
        if ($chunk === false || $chunk === '') {
            throw new CommunicationException(
                $connection, 'Error while reading line from the server'
            );
        }
        $prefix  = $chunk[0];
        $payload = substr($chunk, 1, -2);
        switch ($prefix) {
            case '+':    // inline
                if ($payload === 'OK') {
                    return true;
                }
                if ($payload === 'QUEUED') {
                    return new \Predis\ResponseQueued();
                }
                return $payload;

            case '$':    // bulk
                $size = (int) $payload;
                if ($size === -1) {
                    return null;
                }
                $bulkData = '';
                $bytesLeft = ($size += 2);
                do {
                    $chunk = fread($socket, min($bytesLeft, self::BUFFER_SIZE));
                    if ($chunk === false || $chunk === '') {
                        throw new CommunicationException(
                            $connection, 'Error while reading bytes from the server'
                        );
                    }
                    $bulkData .= $chunk;
                    $bytesLeft = $size - strlen($bulkData);
                } while ($bytesLeft > 0);
                return substr($bulkData, 0, -2);

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
                $errorMessage = substr($payload, 4);
                if ($this->_throwErrors) {
                    throw new \Predis\ServerException($errorMessage);
                }
                return new \Predis\ResponseError($errorMessage);

            default:
                throw new CommunicationException(
                    $connection, "Unknown prefix: '$prefix'"
                );
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
