<?php
require_once 'SharedConfigurations.php';

class SimpleDebuggableConnection extends Predis\Network\TcpConnection {
    private $_debugBuffer = array();
    private $_tstart = 0;

    public function connect() {
        $this->_tstart = microtime(true);
        parent::connect();
    }

    private function storeDebug(Predis\ICommand $command, $direction) {
        $firtsArg  = $command->getArgument(0);
        $timestamp = round(microtime(true) - $this->_tstart, 4);
        $debug  = $command->getCommandId();
        $debug .= isset($firtsArg) ? " $firtsArg " : ' ';
        $debug .= "$direction $this";
        $debug .= " [{$timestamp}s]";
        $this->_debugBuffer[] = $debug;
    }

    public function writeCommand(Predis\ICommand $command) {
        parent::writeCommand($command);
        $this->storeDebug($command, '->');
    }

    public function readResponse(Predis\ICommand $command) {
        $reply = parent::readResponse($command);
        $this->storeDebug($command, '<-');
        return $reply;
    }

    public function getDebugBuffer() {
        return $this->_debugBuffer;
    }
}

$parameters = new Predis\ConnectionParameters($single_server);
$connection = new SimpleDebuggableConnection($parameters);

$redis = new Predis\Client($connection);
$redis->set('foo', 'bar');
$redis->get('foo');
$redis->info();

print_r($connection->getDebugBuffer());

/* OUTPUT:
Array
(
    [0] => SELECT 15 -> 127.0.0.1:6379 [0.0008s]
    [1] => SELECT 15 <- 127.0.0.1:6379 [0.0012s]
    [2] => SET foo -> 127.0.0.1:6379 [0.0014s]
    [3] => SET foo <- 127.0.0.1:6379 [0.0014s]
    [4] => GET foo -> 127.0.0.1:6379 [0.0016s]
    [5] => GET foo <- 127.0.0.1:6379 [0.0018s]
    [6] => INFO -> 127.0.0.1:6379 [0.002s]
    [7] => INFO <- 127.0.0.1:6379 [0.0025s]
)
*/
?>
