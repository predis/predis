<?php
/*
This class implements a Predis connection that actually talks with Webdis
(http://github.com/nicolasff/webdis) instead of connecting directly to Redis.
It relies on the http PECL extension to communicate with the web server and the
phpiredis extension to parse the protocol of the replies returned in the http
response bodies.

Since this connection class is highly experimental, some features have not been
implemented yet (or they simply cannot be implemented at all). Here is a list:

  - Pipelining commands.
  - Publish / Subscribe.
  - MULTI / EXEC transactions (not yet supported by Webdis).
*/

namespace Predis\Network;

use HttpRequest;
use Predis\IConnectionParameters;
use Predis\ResponseError;
use Predis\ClientException;
use Predis\ServerException;
use Predis\CommunicationException;
use Predis\Commands\ICommand;

const ERR_MSG_EXTENSION = 'The %s extension must be loaded in order to be able to use this connection class';

class WebdisConnection implements IConnectionSingle {
    private $_parameters;
    private $_webdisUrl;
    private $_reader;

    private static function throwNotImplementedException($class, $function) {
        throw new \RuntimeException("The method $class::$function() is not implemented");
    }

    public function __construct(IConnectionParameters $parameters) {
        $this->checkExtensions();
        if ($parameters->scheme !== 'http') {
            throw new \InvalidArgumentException("Invalid scheme: {$parameters->scheme}");
        }
        $this->_parameters = $parameters;
        $this->_webdisUrl = "{$parameters->scheme}://{$parameters->host}:{$parameters->port}";
        $this->_reader = $this->initializeReader($parameters);
    }

    public function __destruct() {
        phpiredis_reader_destroy($this->_reader);
    }

    private function checkExtensions() {
        if (!class_exists("HttpRequest")) {
            throw new ClientException(sprintf(ERR_MSG_EXTENSION, 'http'));
        }
        if (!function_exists('phpiredis_reader_create')) {
            throw new ClientException(sprintf(ERR_MSG_EXTENSION, 'phpiredis'));
        }
    }

    private function initializeReader(IConnectionParameters $parameters) {
        $throwErrors = $parameters->throw_errors;
        $reader = phpiredis_reader_create();
        phpiredis_reader_set_status_handler($reader, $this->getStatusHandler());
        phpiredis_reader_set_error_handler($reader, $this->getErrorHandler($throwErrors));
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

    public function connect() {
        // NOOP
    }

    public function disconnect() {
        // NOOP
    }

    public function isConnected() {
        return true;
    }

    public function writeCommand(ICommand $command) {
        self::throwNotImplementedException(__CLASS__, __FUNCTION__);
    }

    public function readResponse(ICommand $command) {
        self::throwNotImplementedException(__CLASS__, __FUNCTION__);
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

    public function executeCommand(ICommand $command) {
        $commandId = $this->getCommandId($command);
        $arguments = implode('/', array_map('urlencode', $command->getArguments()));

        $request = new HttpRequest($this->_webdisUrl, HttpRequest::METH_POST);
        $request->setBody("$commandId/$arguments.raw");
        $request->send();

        phpiredis_reader_feed($this->_reader, $request->getResponseBody());

        $reply = phpiredis_reader_get_reply($this->_reader);
        if ($reply instanceof IReplyObject) {
            return $reply;
        }
        return $command->parseResponse($reply);
    }

    public function getResource() {
        self::throwNotImplementedException(__CLASS__, __FUNCTION__);
    }

    public function getParameters() {
        return $this->_parameters;
    }

    public function pushInitCommand(ICommand $command) {
        self::throwNotImplementedException(__CLASS__, __FUNCTION__);
    }

    public function read() {
        self::throwNotImplementedException(__CLASS__, __FUNCTION__);
    }

    public function __toString() {
        return "{$this->_parameters->host}:{$this->_parameters->port}";
    }
}
