<?php

namespace Predis\Transaction;

use Predis\Client;
use Predis\Helpers;
use Predis\ResponseQueued;
use Predis\ClientException;
use Predis\ServerException;
use Predis\CommunicationException;
use Predis\Protocol\ProtocolException;

class MultiExecContext {
    const STATE_RESET       = 0x00000;
    const STATE_INITIALIZED = 0x00001;
    const STATE_INSIDEBLOCK = 0x00010;
    const STATE_DISCARDED   = 0x00100;
    const STATE_CAS         = 0x01000;
    const STATE_WATCH       = 0x10000;

    private $_state;
    private $_canWatch;
    protected $_client;
    protected $_options;
    protected $_commands;

    public function __construct(Client $client, Array $options = null) {
        $this->checkCapabilities($client);
        $this->_options = $options ?: array();
        $this->_client  = $client;
        $this->reset();
    }

    protected function setState($flags) {
        $this->_state = $flags;
    }

    protected function getState() {
        return $this->_state;
    }

    protected function flagState($flags) {
        $this->_state |= $flags;
    }

    protected function unflagState($flags) {
        $this->_state &= ~$flags;
    }

    protected function checkState($flags) {
        return ($this->_state & $flags) === $flags;
    }

    private function checkCapabilities(Client $client) {
        if (Helpers::isCluster($client->getConnection())) {
            throw new ClientException(
                'Cannot initialize a MULTI/EXEC context over a cluster of connections'
            );
        }
        $profile = $client->getProfile();
        if ($profile->supportsCommands(array('multi', 'exec', 'discard')) === false) {
            throw new ClientException(
                'The current profile does not support MULTI, EXEC and DISCARD'
            );
        }
        $this->_canWatch = $profile->supportsCommands(array('watch', 'unwatch'));
    }

    private function isWatchSupported() {
        if ($this->_canWatch === false) {
            throw new ClientException(
                'The current profile does not support WATCH and UNWATCH'
            );
        }
    }

    protected function reset() {
        $this->setState(self::STATE_RESET);
        $this->_commands = array();
    }

    protected function initialize() {
        if ($this->checkState(self::STATE_INITIALIZED)) {
            return;
        }
        $options = $this->_options;
        if (isset($options['cas']) && $options['cas']) {
            $this->flagState(self::STATE_CAS);
        }
        if (isset($options['watch'])) {
            $this->watch($options['watch']);
        }
        $cas = $this->checkState(self::STATE_CAS);
        $discarded = $this->checkState(self::STATE_DISCARDED);
        if (!$cas || ($cas && $discarded)) {
            $this->_client->multi();
            if ($discarded) {
                $this->unflagState(self::STATE_CAS);
            }
        }
        $this->unflagState(self::STATE_DISCARDED);
        $this->flagState(self::STATE_INITIALIZED);
    }

    public function __call($method, $arguments) {
        $this->initialize();
        $client = $this->_client;
        if ($this->checkState(self::STATE_CAS)) {
            return call_user_func_array(array($client, $method), $arguments);
        }
        $command  = $client->createCommand($method, $arguments);
        $response = $client->executeCommand($command);
        if (!$response instanceof ResponseQueued) {
            $this->onProtocolError('The server did not respond with a QUEUED status reply');
        }
        $this->_commands[] = $command;
        return $this;
    }

    public function watch($keys) {
        $this->isWatchSupported();
        if ($this->checkState(self::STATE_INITIALIZED) && !$this->checkState(self::STATE_CAS)) {
            throw new ClientException('WATCH after MULTI is not allowed');
        }
        $watchReply = $this->_client->watch($keys);
        $this->flagState(self::STATE_WATCH);
        return $watchReply;
    }

    public function multi() {
        if ($this->checkState(self::STATE_INITIALIZED | self::STATE_CAS)) {
            $this->unflagState(self::STATE_CAS);
            $this->_client->multi();
        }
        else {
            $this->initialize();
        }
        return $this;
    }

    public function unwatch() {
        $this->isWatchSupported();
        $this->unflagState(self::STATE_WATCH);
        $this->_client->unwatch();
        return $this;
    }

    public function discard() {
        if ($this->checkState(self::STATE_INITIALIZED)) {
            $command = $this->checkState(self::STATE_CAS) ? 'unwatch' : 'discard';
            $this->_client->$command();
            $this->reset();
            $this->flagState(self::STATE_DISCARDED);
        }
        return $this;
    }

    public function exec() {
        return $this->execute();
    }

    private function checkBeforeExecution($block) {
        if ($this->checkState(self::STATE_INSIDEBLOCK)) {
            throw new ClientException(
                "Cannot invoke 'execute' or 'exec' inside an active client transaction block"
            );
        }
        if ($block) {
            if (!is_callable($block)) {
                throw new \InvalidArgumentException(
                    'Argument passed must be a callable object'
                );
            }
            if (count($this->_commands) > 0) {
                $this->discard();
                throw new ClientException(
                    'Cannot execute a transaction block after using fluent interface'
                );
            }
        }
        if (isset($this->_options['retry']) && !isset($block)) {
            $this->discard();
            throw new \InvalidArgumentException(
                'Automatic retries can be used only when a transaction block is provided'
            );
        }
    }

    public function execute($block = null) {
        $this->checkBeforeExecution($block);

        $reply = null;
        $returnValues = array();
        $attemptsLeft = isset($this->_options['retry']) ? (int)$this->_options['retry'] : 0;

        do {
            if ($block !== null) {
                $this->executeTransactionBlock($block);
            }

            if (count($this->_commands) === 0) {
                if ($this->checkState(self::STATE_WATCH)) {
                    $this->discard();
                }
                return;
            }

            $reply = $this->_client->exec();
            if ($reply === null) {
                if ($attemptsLeft === 0) {
                    $message = 'The current transaction has been aborted by the server';
                    throw new AbortedMultiExecException($this, $message);
                }
                $this->reset();
                if (isset($this->_options['on_retry']) && is_callable($this->_options['on_retry'])) {
                    call_user_func($this->_options['on_retry'], $this, $attemptsLeft);
                }
                continue;
            }
            break;
        } while ($attemptsLeft-- > 0);

        $execReply = $reply instanceof \Iterator ? iterator_to_array($reply) : $reply;
        $sizeofReplies = count($execReply);

        $commands = $this->_commands;
        if ($sizeofReplies !== count($commands)) {
            $this->onProtocolError("EXEC returned an unexpected number of replies");
        }
        for ($i = 0; $i < $sizeofReplies; $i++) {
            $commandReply = $execReply[$i];
            if ($commandReply instanceof \Iterator) {
                $commandReply = iterator_to_array($commandReply);
            }
            $returnValues[$i] = $commands[$i]->parseResponse($commandReply);
            unset($commands[$i]);
        }

        return $returnValues;
    }

    protected function executeTransactionBlock($block) {
        $blockException = null;
        $this->flagState(self::STATE_INSIDEBLOCK);
        try {
            $block($this);
        }
        catch (CommunicationException $exception) {
            $blockException = $exception;
        }
        catch (ServerException $exception) {
            $blockException = $exception;
        }
        catch (\Exception $exception) {
            $blockException = $exception;
            $this->discard();
        }
        $this->unflagState(self::STATE_INSIDEBLOCK);
        if ($blockException !== null) {
            throw $blockException;
        }
    }

    private function onProtocolError($message) {
        // Since a MULTI/EXEC block cannot be initialized over a clustered
        // connection, we can safely assume that Predis\Client::getConnection()
        // will always return an instance of Predis\Network\IConnectionSingle.
        Helpers::onCommunicationException(new ProtocolException(
            $this->_client->getConnection(), $message
        ));
    }
}
