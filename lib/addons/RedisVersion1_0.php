<?php
namespace Predis;

RedisServerProfile::registerProfile('\Predis\RedisServer_v1_0', '1.0');

abstract class InlineCommand extends Command {
    public function serializeRequest($command, $arguments) {
        if (isset($arguments[0]) && is_array($arguments[0])) {
            $arguments[0] = implode($arguments[0], ' ');
        }
        return $command . (count($arguments) > 0
            ? ' ' . implode($arguments, ' ') . Protocol::NEWLINE 
            : Protocol::NEWLINE
        );
    }
}

abstract class BulkCommand extends Command {
    public function serializeRequest($command, $arguments) {
        $data = array_pop($arguments);
        if (is_array($data)) {
            $data = implode($data, ' ');
        }
        return $command . ' ' . implode($arguments, ' ') . ' ' . strlen($data) . 
            Protocol::NEWLINE . $data . Protocol::NEWLINE;
    }
}

class RedisServer_v1_0 extends \Predis\RedisServerProfile {
    public function getVersion() { return '1.0'; }
    public function getSupportedCommands() {
        return array(
            /* miscellaneous commands */
            'ping'            => '\Predis\Compatibility\v1_0\Commands\Ping',
            'echo'            => '\Predis\Compatibility\v1_0\Commands\DoEcho',
            'auth'            => '\Predis\Compatibility\v1_0\Commands\Auth',

            /* connection handling */
            'quit'            => '\Predis\Compatibility\v1_0\Commands\Quit',

            /* commands operating on string values */
            'set'             => '\Predis\Compatibility\v1_0\Commands\Set',
            'setnx'           => '\Predis\Compatibility\v1_0\Commands\SetPreserve',
            'get'             => '\Predis\Compatibility\v1_0\Commands\Get',
            'mget'            => '\Predis\Compatibility\v1_0\Commands\GetMultiple',
            'getset'          => '\Predis\Compatibility\v1_0\Commands\GetSet',
            'incr'            => '\Predis\Compatibility\v1_0\Commands\Increment',
            'incrby'          => '\Predis\Compatibility\v1_0\Commands\IncrementBy',
            'decr'            => '\Predis\Compatibility\v1_0\Commands\Decrement',
            'decrby'          => '\Predis\Compatibility\v1_0\Commands\DecrementBy',
            'exists'          => '\Predis\Compatibility\v1_0\Commands\Exists',
            'del'             => '\Predis\Compatibility\v1_0\Commands\Delete',
            'type'            => '\Predis\Compatibility\v1_0\Commands\Type',

            /* commands operating on the key space */
            'keys'            => '\Predis\Compatibility\v1_0\Commands\Keys',
            'randomkey'       => '\Predis\Compatibility\v1_0\Commands\RandomKey',
            'rename'          => '\Predis\Compatibility\v1_0\Commands\Rename',
            'renamenx'        => '\Predis\Compatibility\v1_0\Commands\RenamePreserve',
            'expire'          => '\Predis\Compatibility\v1_0\Commands\Expire',
            'expireat'        => '\Predis\Compatibility\v1_0\Commands\ExpireAt',
            'dbsize'          => '\Predis\Compatibility\v1_0\Commands\DatabaseSize',
            'ttl'             => '\Predis\Compatibility\v1_0\Commands\TimeToLive',

            /* commands operating on lists */
            'rpush'           => '\Predis\Compatibility\v1_0\Commands\ListPushTail',
            'lpush'           => '\Predis\Compatibility\v1_0\Commands\ListPushHead',
            'llen'            => '\Predis\Compatibility\v1_0\Commands\ListLength',
            'lrange'          => '\Predis\Compatibility\v1_0\Commands\ListRange',
            'ltrim'           => '\Predis\Compatibility\v1_0\Commands\ListTrim',
            'lindex'          => '\Predis\Compatibility\v1_0\Commands\ListIndex',
            'lset'            => '\Predis\Compatibility\v1_0\Commands\ListSet',
            'lrem'            => '\Predis\Compatibility\v1_0\Commands\ListRemove',
            'lpop'            => '\Predis\Compatibility\v1_0\Commands\ListPopFirst',
            'rpop'            => '\Predis\Compatibility\v1_0\Commands\ListPopLast',

            /* commands operating on sets */
            'sadd'            => '\Predis\Compatibility\v1_0\Commands\SetAdd', 
            'srem'            => '\Predis\Compatibility\v1_0\Commands\SetRemove', 
            'spop'            => '\Predis\Compatibility\v1_0\Commands\SetPop',
            'smove'           => '\Predis\Compatibility\v1_0\Commands\SetMove', 
            'scard'           => '\Predis\Compatibility\v1_0\Commands\SetCardinality', 
            'sismember'       => '\Predis\Compatibility\v1_0\Commands\SetIsMember', 
            'sinter'          => '\Predis\Compatibility\v1_0\Commands\SetIntersection', 
            'sinterstore'     => '\Predis\Compatibility\v1_0\Commands\SetIntersectionStore', 
            'sunion'          => '\Predis\Compatibility\v1_0\Commands\SetUnion', 
            'sunionstore'     => '\Predis\Compatibility\v1_0\Commands\SetUnionStore', 
            'sdiff'           => '\Predis\Compatibility\v1_0\Commands\SetDifference', 
            'sdiffstore'      => '\Predis\Compatibility\v1_0\Commands\SetDifferenceStore', 
            'smembers'        => '\Predis\Compatibility\v1_0\Commands\SetMembers', 
            'srandmember'     => '\Predis\Compatibility\v1_0\Commands\SetRandomMember', 

            /* multiple databases handling commands */
            'select'          => '\Predis\Compatibility\v1_0\Commands\SelectDatabase', 
            'move'            => '\Predis\Compatibility\v1_0\Commands\MoveKey', 
            'flushdb'         => '\Predis\Compatibility\v1_0\Commands\FlushDatabase', 
            'flushall'        => '\Predis\Compatibility\v1_0\Commands\FlushAll', 

            /* sorting */
            'sort'            => '\Predis\Compatibility\v1_0\Commands\Sort',

            /* remote server control commands */
            'info'            => '\Predis\Compatibility\v1_0\Commands\Info',
            'slaveof'         => '\Predis\Compatibility\v1_0\Commands\SlaveOf', 

            /* persistence control commands */
            'save'            => '\Predis\Compatibility\v1_0\Commands\Save',
            'bgsave'          => '\Predis\Compatibility\v1_0\Commands\BackgroundSave', 
            'lastsave'        => '\Predis\Compatibility\v1_0\Commands\LastSave', 
            'shutdown'        => '\Predis\Compatibility\v1_0\Commands\Shutdown',
        );
    }
}

namespace Predis\Compatibility\v1_0\Commands;

/* miscellaneous commands */
class Ping extends  \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PING'; }
    public function parseResponse($data) {
        return $data === 'PONG' ? true : false;
    }
}

class DoEcho extends \Predis\BulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'ECHO'; }
}

class Auth extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'AUTH'; }
}

/* connection handling */
class Quit extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'QUIT'; }
    public function closesConnection() { return true; }
}

/* commands operating on string values */
class Set extends \Predis\BulkCommand {
    public function getCommandId() { return 'SET'; }
}

class SetPreserve extends \Predis\BulkCommand {
    public function getCommandId() { return 'SETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Get extends \Predis\InlineCommand {
    public function getCommandId() { return 'GET'; }
}

class GetMultiple extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MGET'; }
}

class GetSet extends \Predis\BulkCommand {
    public function getCommandId() { return 'GETSET'; }
}

class Increment extends \Predis\InlineCommand {
    public function getCommandId() { return 'INCR'; }
}

class IncrementBy extends \Predis\InlineCommand {
    public function getCommandId() { return 'INCRBY'; }
}

class Decrement extends \Predis\InlineCommand {
    public function getCommandId() { return 'DECR'; }
}

class DecrementBy extends \Predis\InlineCommand {
    public function getCommandId() { return 'DECRBY'; }
}

class Exists extends \Predis\InlineCommand {
    public function getCommandId() { return 'EXISTS'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Delete extends \Predis\InlineCommand {
    public function getCommandId() { return 'DEL'; }
}

class Type extends \Predis\InlineCommand {
    public function getCommandId() { return 'TYPE'; }
}

/* commands operating on the key space */
class Keys extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'KEYS'; }
    public function parseResponse($data) { 
        return strlen($data) > 0 ? explode(' ', $data) : array();
    }
}

class RandomKey extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RANDOMKEY'; }
    public function parseResponse($data) { return $data !== '' ? $data : null; }
}

class Rename extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAME'; }
}

class RenamePreserve extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAMENX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Expire extends \Predis\InlineCommand {
    public function getCommandId() { return 'EXPIRE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class ExpireAt extends \Predis\InlineCommand {
    public function getCommandId() { return 'EXPIREAT'; }
    public function parseResponse($data) { return (bool) $data; }
}

class DatabaseSize extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'DBSIZE'; }
}

class TimeToLive extends \Predis\InlineCommand {
    public function getCommandId() { return 'TTL'; }
}

/* commands operating on lists */
class ListPushTail extends \Predis\BulkCommand {
    public function getCommandId() { return 'RPUSH'; }
}

class ListPushHead extends \Predis\BulkCommand {
    public function getCommandId() { return 'LPUSH'; }
}

class ListLength extends \Predis\InlineCommand {
    public function getCommandId() { return 'LLEN'; }
}

class ListRange extends \Predis\InlineCommand {
    public function getCommandId() { return 'LRANGE'; }
}

class ListTrim extends \Predis\InlineCommand {
    public function getCommandId() { return 'LTRIM'; }
}

class ListIndex extends \Predis\InlineCommand {
    public function getCommandId() { return 'LINDEX'; }
}

class ListSet extends \Predis\BulkCommand {
    public function getCommandId() { return 'LSET'; }
}

class ListRemove extends \Predis\BulkCommand {
    public function getCommandId() { return 'LREM'; }
}

class ListPopFirst extends \Predis\InlineCommand {
    public function getCommandId() { return 'LPOP'; }
}

class ListPopLast extends \Predis\InlineCommand {
    public function getCommandId() { return 'RPOP'; }
}

/* commands operating on sets */
class SetAdd extends \Predis\BulkCommand {
    public function getCommandId() { return 'SADD'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetRemove extends \Predis\BulkCommand {
    public function getCommandId() { return 'SREM'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetPop  extends \Predis\InlineCommand {
    public function getCommandId() { return 'SPOP'; }
}

class SetMove extends \Predis\BulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SMOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetCardinality extends \Predis\InlineCommand {
    public function getCommandId() { return 'SCARD'; }
}

class SetIsMember extends \Predis\BulkCommand {
    public function getCommandId() { return 'SISMEMBER'; }
    public function parseResponse($data) { return (bool) $data; }
}

class SetIntersection extends \Predis\InlineCommand {
    public function getCommandId() { return 'SINTER'; }
}

class SetIntersectionStore extends \Predis\InlineCommand {
    public function getCommandId() { return 'SINTERSTORE'; }
}

class SetUnion extends \Predis\InlineCommand {
    public function getCommandId() { return 'SUNION'; }
}

class SetUnionStore extends \Predis\InlineCommand {
    public function getCommandId() { return 'SUNIONSTORE'; }
}

class SetDifference extends \Predis\InlineCommand {
    public function getCommandId() { return 'SDIFF'; }
}

class SetDifferenceStore extends \Predis\InlineCommand {
    public function getCommandId() { return 'SDIFFSTORE'; }
}

class SetMembers extends \Predis\InlineCommand {
    public function getCommandId() { return 'SMEMBERS'; }
}

class SetRandomMember extends \Predis\InlineCommand {
    public function getCommandId() { return 'SRANDMEMBER'; }
}

/* multiple databases handling commands */
class SelectDatabase extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SELECT'; }
}

class MoveKey extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class FlushDatabase extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHDB'; }
}

class FlushAll extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHALL'; }
}

/* sorting */
class Sort extends \Predis\InlineCommand {
    public function getCommandId() { return 'SORT'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 1) {
            return $arguments;
        }

        // TODO: add more parameters checks
        $query = array($arguments[0]);
        $sortParams = $arguments[1];

        if (isset($sortParams['by'])) {
            $query[] = 'BY';
            $query[] = $sortParams['by'];
        }
        if (isset($sortParams['get'])) {
            $getargs = $sortParams['get'];
            if (is_array($getargs)) {
                foreach ($getargs as $getarg) {
                    $query[] = 'GET';
                    $query[] = $getarg;
                }
            }
            else {
                $query[] = 'GET';
                $query[] = $getargs;
            }
        }
        if (isset($sortParams['limit']) && is_array($sortParams['limit'])) {
            $query[] = 'LIMIT';
            $query[] = $sortParams['limit'][0];
            $query[] = $sortParams['limit'][1];
        }
        if (isset($sortParams['sort'])) {
            $query[] = strtoupper($sortParams['sort']);
        }
        if (isset($sortParams['alpha']) && $sortParams['alpha'] == true) {
            $query[] = 'ALPHA';
        }
        if (isset($sortParams['store']) && $sortParams['store'] == true) {
            $query[] = 'STORE';
            $query[] = $sortParams['store'];
        }

        return $query;
    }
}

/* persistence control commands */
class Save extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SAVE'; }
}

class BackgroundSave extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'BGSAVE'; }
    public function parseResponse($data) {
        if ($data == 'Background saving started') {
            return true;
        }
        return $data;
    }
}

class LastSave extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'LASTSAVE'; }
}

class Shutdown extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SHUTDOWN'; }
    public function closesConnection() { return true; }
}

/* remote server control commands */
class Info extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'INFO'; }
    public function parseResponse($data) {
        $info      = array();
        $infoLines = explode("\r\n", $data, -1);
        foreach ($infoLines as $row) {
            list($k, $v) = explode(':', $row);
            if (!preg_match('/^db\d+$/', $k)) {
                $info[$k] = $v;
            }
            else {
                $db = array();
                foreach (explode(',', $v) as $dbvar) {
                    list($dbvk, $dbvv) = explode('=', $dbvar);
                    $db[trim($dbvk)] = $dbvv;
                }
                $info[$k] = $db;
            }
        }
        return $info;
    }
}

class SlaveOf extends \Predis\InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SLAVEOF'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 0 || $arguments[0] === 'NO ONE') {
            return array('NO', 'ONE');
        }
        return $arguments;
    }
}
?>
