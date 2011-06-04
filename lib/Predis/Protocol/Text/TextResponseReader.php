<?php

namespace Predis\Protocol\Text;

use Predis\Helpers;
use Predis\Protocol\IResponseReader;
use Predis\Protocol\IResponseHandler;
use Predis\Protocol\ProtocolException;
use Predis\Network\IConnectionComposable;

class TextResponseReader implements IResponseReader {
    private $_prefixHandlers;

    public function __construct() {
        $this->_prefixHandlers = $this->getDefaultHandlers();
    }

    private function getDefaultHandlers() {
        return array(
            TextProtocol::PREFIX_STATUS     => new ResponseStatusHandler(),
            TextProtocol::PREFIX_ERROR      => new ResponseErrorHandler(),
            TextProtocol::PREFIX_INTEGER    => new ResponseIntegerHandler(),
            TextProtocol::PREFIX_BULK       => new ResponseBulkHandler(),
            TextProtocol::PREFIX_MULTI_BULK => new ResponseMultiBulkHandler(),
        );
    }

    public function setHandler($prefix, IResponseHandler $handler) {
        $this->_prefixHandlers[$prefix] = $handler;
    }

    public function getHandler($prefix) {
        if (isset($this->_prefixHandlers[$prefix])) {
            return $this->_prefixHandlers[$prefix];
        }
    }

    public function read(IConnectionComposable $connection) {
        $header = $connection->readLine();
        if ($header === '') {
            $this->protocolError($connection, 'Unexpected empty header');
        }

        $prefix = $header[0];
        if (!isset($this->_prefixHandlers[$prefix])) {
            $this->protocolError($connection, "Unknown prefix '$prefix'");
        }
        $handler = $this->_prefixHandlers[$prefix];
        return $handler->handle($connection, substr($header, 1));
    }

    private function protocolError(IConnectionComposable $connection, $message) {
        Helpers::onCommunicationException(new ProtocolException($connection, $message));
    }
}
