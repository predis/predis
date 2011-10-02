<?php
/*
This class implements a Predis connection that actually talks with Webdis
(http://github.com/nicolasff/webdis) instead of connecting directly to Redis.
It relies on the cURL extension to communicate with the web server and the
phpiredis extension to parse the protocol of the replies returned in the http
response bodies.

Some features are not yet available or they simply cannot be implemented:
  - Pipelining commands.
  - Publish / Subscribe.
  - MULTI / EXEC transactions (not yet supported by Webdis).
*/

namespace Predis\Network;

use Predis\IConnectionParameters;
use Predis\ResponseError;
use Predis\ClientException;
use Predis\ServerException;
use Predis\Commands\ICommand;
use Predis\Protocol\ProtocolException;

const ERR_MSG_EXTENSION = 'The %s extension must be loaded in order to be able to use this connection class';

class WebdisConnection implements IConnectionSingle {
    private $_parameters;
    private $_resource;
    private $_reader;

    public function __construct(IConnectionParameters $parameters) {
        $this->_parameters = $parameters;
        if ($parameters->scheme !== 'http') {
            throw new \InvalidArgumentException("Invalid scheme: {$parameters->scheme}");
        }
        $this->checkExtensions();
        $this->_resource = $this->initializeCurl($parameters);
        $this->_reader = $this->initializeReader($parameters);
    }

    public function __destruct() {
        curl_close($this->_resource);
        phpiredis_reader_destroy($this->_reader);
    }

    private function throwNotSupportedException($function) {
        $class = __CLASS__;
        throw new \RuntimeException("The method $class::$function() is not supported");
    }

    private function checkExtensions() {
        if (!function_exists('curl_init')) {
            throw new ClientException(sprintf(ERR_MSG_EXTENSION, 'curl'));
        }
        if (!function_exists('phpiredis_reader_create')) {
            throw new ClientException(sprintf(ERR_MSG_EXTENSION, 'phpiredis'));
        }
    }

    private function initializeCurl(IConnectionParameters $parameters) {
        $options = array(
            CURLOPT_FAILONERROR => true,
            CURLOPT_CONNECTTIMEOUT_MS => $parameters->connection_timeout * 1000,
            CURLOPT_URL => "{$parameters->scheme}://{$parameters->host}:{$parameters->port}",
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST => true,
            CURLOPT_WRITEFUNCTION => array($this, 'feedReader'),
        );

        if (isset($parameters->user, $parameters->pass)) {
            $options[CURLOPT_USERPWD] = "{$parameters->user}:{$parameters->pass}";
        }

        $resource = curl_init();
        curl_setopt_array($resource, $options);

        return $resource;
    }

    private function initializeReader(IConnectionParameters $parameters) {
        $reader = phpiredis_reader_create();
        phpiredis_reader_set_status_handler($reader, $this->getStatusHandler());
        phpiredis_reader_set_error_handler($reader, $this->getErrorHandler($parameters->throw_errors));
        return $reader;
    }

    private function getStatusHandler() {
        return function($payload) {
            return $payload === 'OK' ? true : $payload;
        };
    }

    private function getErrorHandler($throwErrors) {
        if ($throwErrors) {
            return function($errorMessage) {
                throw new ServerException($errorMessage);
            };
        }
        return function($errorMessage) {
            return new ResponseError($errorMessage);
        };
    }

    protected function feedReader($resource, $buffer) {
        phpiredis_reader_feed($this->_reader, $buffer);
        return strlen($buffer);
    }

    public function connect() {
        // NOOP
    }

    public function disconnect() {
        // NOOP
    }

    public function isConnected() {
        return true;
    }

    protected function getCommandId(ICommand $command) {
        switch (($commandId = $command->getId())) {
            case 'AUTH':
            case 'SELECT':
            case 'MULTI':
            case 'EXEC':
            case 'WATCH':
            case 'UNWATCH':
            case 'DISCARD':
                throw new \InvalidArgumentException("Disabled command: {$command->getId()}");
            default:
                return $commandId;
        }
    }

    public function writeCommand(ICommand $command) {
        $this->throwNotSupportedException(__FUNCTION__);
    }

    public function readResponse(ICommand $command) {
        $this->throwNotSupportedException(__FUNCTION__);
    }

    public function executeCommand(ICommand $command) {
        $resource = $this->_resource;
        $commandId = $this->getCommandId($command);

        if ($arguments = $command->getArguments()) {
            $arguments = implode('/', array_map('urlencode', $arguments));
            $serializedCommand = "$commandId/$arguments.raw";
        }
        else {
            $serializedCommand = "$commandId.raw";
        }

        curl_setopt($resource, CURLOPT_POSTFIELDS, $serializedCommand);
        if (curl_exec($resource) === false) {
            $error = curl_error($resource);
            $errno = curl_errno($resource);
            throw new ConnectionException($this, trim($error), $errno);
        }

        $readerState = phpiredis_reader_get_state($this->_reader);
        if ($readerState === PHPIREDIS_READER_STATE_COMPLETE) {
            $reply = phpiredis_reader_get_reply($this->_reader);
            if ($reply instanceof IReplyObject) {
                return $reply;
            }
            return $command->parseResponse($reply);
        }
        else {
            $error = phpiredis_reader_get_error($this->_reader);
            throw new ProtocolException($this, $error);
        }
    }

    public function getResource() {
        return $this->_resource;
    }

    public function getParameters() {
        return $this->_parameters;
    }

    public function pushInitCommand(ICommand $command) {
        $this->throwNotSupportedException(__FUNCTION__);
    }

    public function read() {
        $this->throwNotSupportedException(__FUNCTION__);
    }

    public function __toString() {
        return "{$this->_parameters->host}:{$this->_parameters->port}";
    }
}
