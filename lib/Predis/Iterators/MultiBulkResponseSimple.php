<?php

namespace Predis\Iterators;

use Predis\Network\IConnection;
use Predis\Network\IConnectionSingle;

class MultiBulkResponseSimple extends MultiBulkResponse {
    private $_connection;

    public function __construct(IConnectionSingle $connection, $size) {
        $this->_connection = $connection;
        $this->_position   = 0;
        $this->_current    = $size > 0 ? $this->getValue() : null;
        $this->_replySize  = $size;
    }

    public function __destruct() {
        // When the iterator is garbage-collected (e.g. it goes out of the
        // scope of a foreach) but it has not reached its end, we must sync
        // the client with the queued elements that have not been read from
        // the connection with the server.
        $this->sync();
    }

    public function sync($drop = false) {
        if ($drop == true) {
            if ($this->valid()) {
                $this->_position = $this->_replySize;
                $this->_connection->disconnect();
            }
        }
        else {
            while ($this->valid()) {
                $this->next();
            }
        }
    }

    protected function getValue() {
        return $this->_connection->read();
    }
}
