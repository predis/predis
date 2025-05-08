<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use Predis\CommunicationException;
use Predis\Connection\Resource\Exception\StreamInitException;
use Predis\Connection\Resource\StreamFactory;
use Predis\Connection\Resource\StreamFactoryInterface;
use Predis\Consumer\Push\PushNotificationException;
use Predis\Consumer\Push\PushResponse;
use Predis\Protocol\Parser\Strategy\Resp2Strategy;
use Predis\Protocol\Parser\Strategy\Resp3Strategy;
use Predis\Protocol\Parser\UnexpectedTypeException;
use Predis\Response\Error;
use Predis\Response\ErrorInterface as ErrorResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Standard connection to Redis servers implemented on top of PHP's streams.
 * The connection parameters supported by this class are:.
 *
 *  - scheme: it can be either 'redis', 'tcp', 'rediss', 'tls' or 'unix'.
 *  - host: hostname or IP address of the server.
 *  - port: TCP port of the server.
 *  - path: path of a UNIX domain socket when scheme is 'unix'.
 *  - timeout: timeout to perform the connection (default is 5 seconds).
 *  - read_write_timeout: timeout of read / write operations.
 *  - async_connect: performs the connection asynchronously.
 *  - tcp_nodelay: enables or disables Nagle's algorithm for coalescing.
 *  - persistent: the connection is left intact after a GC collection.
 *  - ssl: context options array (see http://php.net/manual/en/context.ssl.php)
 *
 * @method StreamInterface getResource()
 */
class StreamConnection extends AbstractConnection
{
    /**
     * @var StreamFactoryInterface
     */
    protected $streamFactory;

    /**
     * @param ParametersInterface         $parameters
     * @param StreamFactoryInterface|null $factory
     */
    public function __construct(ParametersInterface $parameters, ?StreamFactoryInterface $factory = null)
    {
        parent::__construct($parameters);
        $this->parameters->conn_uid = spl_object_hash($this);

        $this->streamFactory = $factory ?? new StreamFactory();
    }

    /**
     * Disconnects from the server and destroys the underlying resource when the
     * garbage collector kicks in only if the connection has not been marked as
     * persistent.
     */
    public function __destruct()
    {
        if (isset($this->parameters->persistent) && $this->parameters->persistent) {
            return;
        }

        $this->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    protected function createResource(): StreamInterface
    {
        return $this->streamFactory->createStream($this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if (parent::connect() && $this->initCommands) {
            foreach ($this->initCommands as $command) {
                $response = $this->executeCommand($command);

                $this->handleOnConnectResponse($response, $command);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            $this->getResource()->close();

            parent::disconnect();
        }
    }

    /**
     * {@inheritDoc}
     * @throws CommunicationException
     */
    public function write(string $buffer): void
    {
        $stream = $this->getResource();

        while (($length = strlen($buffer)) > 0) {
            try {
                $written = $stream->write($buffer);
            } catch (RuntimeException $e) {
                $this->onStreamError($e, 'Error while writing bytes to the server.');
            }

            if ($length === $written) { // @phpstan-ignore-line
                return;
            }

            $buffer = substr($buffer, $written); // @phpstan-ignore-line
        }
    }

    /**
     * {@inheritdoc}
     * @throws PushNotificationException
     * @throws StreamInitException|CommunicationException
     */
    public function read()
    {
        $stream = $this->getResource();

        if ($stream->eof()) {
            $this->onStreamError(new RuntimeException('Stream is already at the end'), '');
        }

        try {
            $chunk = $stream->read(-1);
        } catch (RuntimeException $e) {
            $this->onStreamError($e, 'Error while reading line from the server.');
        }

        try {
            $parsedData = $this->parserStrategy->parseData($chunk); // @phpstan-ignore-line
        } catch (UnexpectedTypeException $e) {
            $this->onProtocolError("Unknown response prefix: '{$e->getType()}'.");

            return;
        }

        if (!is_array($parsedData)) {
            return $parsedData;
        }

        switch ($parsedData['type']) {
            case Resp3Strategy::TYPE_PUSH:
                $data = [];

                for ($i = 0; $i < $parsedData['value']; ++$i) {
                    $data[$i] = $this->read();
                }

                return new PushResponse($data);
            case Resp2Strategy::TYPE_ARRAY:
                $data = [];

                for ($i = 0; $i < $parsedData['value']; ++$i) {
                    $data[$i] = $this->read();
                }

                return $data;

            case Resp2Strategy::TYPE_BULK_STRING:
                $bulkData = $this->readByChunks($stream, $parsedData['value']);

                return substr($bulkData, 0, -2);

            case Resp3Strategy::TYPE_VERBATIM_STRING:
                $bulkData = $this->readByChunks($stream, $parsedData['value']);

                return substr($bulkData, $parsedData['offset'], -2);

            case Resp3Strategy::TYPE_BLOB_ERROR:
                $errorMessage = $this->readByChunks($stream, $parsedData['value']);

                return new Error(substr($errorMessage, 0, -2));

            case Resp3Strategy::TYPE_MAP:
                $data = [];

                for ($i = 0; $i < $parsedData['value']; ++$i) {
                    $key = $this->read();
                    $data[$key] = $this->read();
                }

                return $data;

            case Resp3Strategy::TYPE_SET:
                $data = [];

                for ($i = 0; $i < $parsedData['value']; ++$i) {
                    $element = $this->read();

                    if (!in_array($element, $data, true)) {
                        $data[] = $element;
                    }
                }

                return $data;
        }

        return $parsedData;
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        $buffer = $command->serializeCommand();
        $this->write($buffer);
    }

    /**
     * {@inheritDoc}
     */
    public function hasDataToRead(): bool
    {
        return !$this->getResource()->eof();
    }

    /**
     * Reads given resource split on chunks with given size.
     *
     * @param  StreamInterface        $stream
     * @param  int                    $chunkSize
     * @return string
     * @throws CommunicationException
     */
    private function readByChunks(StreamInterface $stream, int $chunkSize): string
    {
        $string = '';
        $bytesLeft = ($chunkSize += 2);

        do {
            try {
                $chunk = $stream->read(min($bytesLeft, 4096));
            } catch (RuntimeException $e) {
                $this->onStreamError($e, 'Error while reading bytes from the server.');
            }

            $string .= $chunk; // @phpstan-ignore-line
            $bytesLeft = $chunkSize - strlen($string);
        } while ($bytesLeft > 0);

        return $string;
    }

    /**
     * Handle response from on-connect command.
     *
     * @param                         $response
     * @param  CommandInterface       $command
     * @return void
     * @throws CommunicationException
     */
    private function handleOnConnectResponse($response, CommandInterface $command): void
    {
        if ($response instanceof ErrorResponseInterface) {
            $this->handleError($response, $command);
        }

        if ($command->getId() === 'HELLO' && is_array($response)) {
            // Searching for the CLIENT ID in RESP2 connection tricky because no dictionaries.
            if (
                $this->getParameters()->protocol == 2
                && false !== $key = array_search('id', $response, true)
            ) {
                $this->clientId = $response[$key + 1];
            } elseif ($this->getParameters()->protocol == 3) {
                $this->clientId = $response['id'];
            }
        }
    }

    /**
     * Handle server errors.
     *
     * @param  ErrorResponseInterface $error
     * @param  CommandInterface       $failedCommand
     * @return void
     * @throws CommunicationException
     */
    private function handleError(ErrorResponseInterface $error, CommandInterface $failedCommand): void
    {
        if ($failedCommand->getId() === 'CLIENT') {
            // Do nothing on CLIENT SETINFO command failure
            return;
        }

        if ($failedCommand->getId() === 'HELLO') {
            if (in_array('AUTH', $failedCommand->getArguments(), true)) {
                $parameters = $this->getParameters();

                // If Redis <= 6.0
                $auth = new RawCommand('AUTH', [$parameters->password]);
                $response = $this->executeCommand($auth);

                if ($response instanceof ErrorResponseInterface) {
                    $this->onConnectionError("Failed: {$response->getMessage()}");
                }
            }

            $setName = new RawCommand('CLIENT', ['SETNAME', 'predis']);
            $response = $this->executeCommand($setName);
            $this->handleOnConnectResponse($response, $setName);

            return;
        }

        $this->onConnectionError("Failed: {$error->getMessage()}");
    }

    /**
     * Handles stream-related exceptions.
     *
     * @param  RuntimeException                        $e
     * @param  string|null                             $message
     * @throws RuntimeException|CommunicationException
     */
    protected function onStreamError(RuntimeException $e, ?string $message = null)
    {
        // Code = 1 represents issues related to read/write operation.
        if ($e->getCode() === 1) {
            $this->onConnectionError($message);
        }

        throw $e;
    }
}
