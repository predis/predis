<?php

namespace Predis;

class MultiExecContext {
    private $_initialized, $_discarded, $_insideBlock, $_checkAndSet, $_watchedKeys;
    private $_client, $_options, $_commands, $_supportsWatch;

    public function __construct(Client $client, Array $options = null) {
        $this->checkCapabilities($client);
        $this->_options = $options ?: array();
        $this->_client  = $client;
        $this->reset();
    }

    private function checkCapabilities(Client $client) {
        if (Utils::isCluster($client->getConnection())) {
            throw new ClientException(
                'Cannot initialize a MULTI/EXEC context over a cluster of connections'
            );
        }
        $profile = $client->getProfile();
        if ($profile->supportsCommands(array('multi', 'exec', 'discard')) === false) {
            throw new ClientException(
                'The current profile does not support MULTI, EXEC and DISCARD commands'
            );
        }
        $this->_supportsWatch = $profile->supportsCommands(array('watch', 'unwatch'));
    }

    private function isWatchSupported() {
        if ($this->_supportsWatch === false) {
            throw new ClientException(
                'The current profile does not support WATCH and UNWATCH commands'
            );
        }
    }

    private function reset() {
        $this->_initialized = false;
        $this->_discarded   = false;
        $this->_checkAndSet = false;
        $this->_insideBlock = false;
        $this->_watchedKeys = false;
        $this->_commands    = array();
    }

    private function initialize() {
        if ($this->_initialized === true) {
            return;
        }
        $options = $this->_options;
        $this->_checkAndSet = isset($options['cas']) && $options['cas'];
        if (isset($options['watch'])) {
            $this->watch($options['watch']);
        }
        if (!$this->_checkAndSet || ($this->_discarded && $this->_checkAndSet)) {
            $this->_client->multi();
            if ($this->_discarded) {
                $this->_checkAndSet = false;
            }
        }
        $this->_initialized = true;
        $this->_discarded   = false;
    }

    public function __call($method, $arguments) {
        $this->initialize();
        $client = $this->_client;
        if ($this->_checkAndSet) {
            return call_user_func_array(array($client, $method), $arguments);
        }
        $command  = $client->createCommand($method, $arguments);
        $response = $client->executeCommand($command);
        if (!$response instanceof ResponseQueued) {
            $this->malformedServerResponse(
                'The server did not respond with a QUEUED status reply'
            );
        }
        $this->_commands[] = $command;
        return $this;
    }

    public function watch($keys) {
        $this->isWatchSupported();
        $this->_watchedKeys = true;
        if ($this->_initialized && !$this->_checkAndSet) {
            throw new ClientException('WATCH inside MULTI is not allowed');
        }
        return $this->_client->watch($keys);
    }

    public function multi() {
        if ($this->_initialized && $this->_checkAndSet) {
            $this->_checkAndSet = false;
            $this->_client->multi();
            return $this;
        }
        $this->initialize();
        return $this;
    }

    public function unwatch() {
        $this->isWatchSupported();
        $this->_watchedKeys = false;
        $this->_client->unwatch();
        return $this;
    }

    public function discard() {
        if ($this->_initialized === true || $this->_checkAndSet) {
            $this->_client->discard();
            $this->reset();
            $this->_discarded = true;
        }
        return $this;
    }

    public function exec() {
        return $this->execute();
    }

    private function checkBeforeExecution($block) {
        if ($this->_insideBlock === true) {
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
            $blockException = null;
            if ($block !== null) {
                $this->_insideBlock = true;
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
                $this->_insideBlock = false;
                if ($blockException !== null) {
                    throw $blockException;
                }
            }

            if (count($this->_commands) === 0) {
                if ($this->_watchedKeys) {
                    $this->_client->discard();
                    return;
                }
                return;
            }

            $reply = $this->_client->exec();
            if ($reply === null) {
                if ($attemptsLeft === 0) {
                    throw new AbortedMultiExec(
                        'The current transaction has been aborted by the server'
                    );
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

        $commands = &$this->_commands;
        if ($sizeofReplies !== count($commands)) {
            $this->malformedServerResponse(
                'Unexpected number of responses for a MultiExecContext'
            );
        }
        for ($i = 0; $i < $sizeofReplies; $i++) {
            $returnValues[] = $commands[$i]->parseResponse($execReply[$i] instanceof \Iterator
                ? iterator_to_array($execReply[$i])
                : $execReply[$i]
            );
            unset($commands[$i]);
        }

        return $returnValues;
    }

    private function malformedServerResponse($message) {
        // Since a MULTI/EXEC block cannot be initialized over a clustered
        // connection, we can safely assume that Predis\Client::getConnection()
        // will always return an instance of Predis\Network\IConnectionSingle.
        Utils::onCommunicationException(new MalformedServerResponse(
            $this->_client->getConnection(), $message
        ));
    }
}
