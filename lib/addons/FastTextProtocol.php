<?php
namespace Predis\Protocols;

use Predis\Utils;
use Predis\ICommand;
use Predis\CommunicationException;
use Predis\Network\IConnectionSingle;
use Predis\Iterators\MultiBulkResponseSimple;

class FastTextProtocol implements IRedisProtocol {
    private $_mbiterable, $_throwErrors;

    public function __construct() {
        $this->_mbiterable  = false;
        $this->_throwErrors = true;
    }

    public function write(IConnectionSingle $connection, ICommand $command) {
        $commandId = $command->getCommandId();
        $arguments = $command->getArguments();

        $cmdlen  = strlen($commandId);
        $reqlen  = count($arguments) + 1;

        $buffer = "*{$reqlen}\r\n\${$cmdlen}\r\n{$commandId}\r\n";
        for ($i = 0; $i < $reqlen - 1;  $i++) {
            $argument = $arguments[$i];
            $arglen  = strlen($argument);
            $buffer .= "\${$arglen}\r\n{$argument}\r\n";
        }

        fwrite($connection->getResource(), $buffer);
    }

    public function read(IConnectionSingle $connection) {
        $bufferSize = 8192;
        $socket = $connection->getResource();
        $chunk  = fgets($socket);
        if ($chunk === false || $chunk === '') {
            throw new CommunicationException(
                $connection, 'Error while reading line from the server'
            );
        }
        $prefix  = $chunk[0];
        $payload = substr($chunk, 1);
        switch ($prefix) {
            case '+':    // inline
                $status = substr($payload, 0, -2);
                if ($status === 'OK') {
                    return true;
                }
                if ($status === 'QUEUED') {
                    return new \Predis\ResponseQueued();
                }
                return $status;

            case '$':    // bulk
                $size = (int) $payload;
                if ($size === -1) {
                    return null;
                }
                $bulkData = '';
                $bytesLeft = ($size += 2);
                do {
                    $chunk = fread($socket, min($bytesLeft, $bufferSize));
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
                    $chunk = fgets($socket);
                    if ($chunk === false || $chunk === '') {
                        throw new CommunicationException(
                            $connection, 'Error while reading line from the server'
                        );
                    }
                    $size = (int) substr($chunk, 1);
                    if ($size === -1) {
                        return $multibulk;
                    }
                    $bulkData = '';
                    $bytesLeft = ($size += 2);
                    do {
                        $chunk = fread($socket, min($bytesLeft, $bufferSize));
                        if ($chunk === false || $chunk === '') {
                            throw new CommunicationException(
                                $connection, 'Error while reading bytes from the server'
                            );
                        }
                        $bulkData .= $chunk;
                        $bytesLeft = $size - strlen($bulkData);
                    } while ($bytesLeft > 0);
                    $multibulk[$i] = substr($bulkData, 0, -2);
                }
                return $multibulk;

            case ':':    // integer
                return (int) $payload;

            case '-':    // error
                if ($this->_throwErrors) {
                    throw new Exception($payload);
                }
                return new Predis\ResponseError($payload);

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
            case 'throw_on_error':
                $this->_throwErrors = (bool) $value;
                break;
        }
    }
}
?>
