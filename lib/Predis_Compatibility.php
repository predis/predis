<?php
Predis_RedisServerProfile::registerProfile('Predis_RedisServer_v1_0', '1.0');

class Predis_RedisServer_v1_0 extends Predis_RedisServerProfile {
    public function getVersion() { return '1.0'; }
    public function getSupportedCommands() {
        return array(
            /* miscellaneous commands */
            'ping'      => 'Predis_Compatibility_v1_0_Commands_Ping',
            'echo'      => 'Predis_Compatibility_v1_0_Commands_DoEcho',
            'auth'      => 'Predis_Compatibility_v1_0_Commands_Auth',

            /* connection handling */
            'quit'      => 'Predis_Compatibility_v1_0_Commands_Quit',

            /* commands operating on string values */
            'set'                     => 'Predis_Compatibility_v1_0_Commands_Set',
            'setnx'                   => 'Predis_Compatibility_v1_0_Commands_SetPreserve',
                'setPreserve'         => 'Predis_Compatibility_v1_0_Commands_SetPreserve',
            'get'                     => 'Predis_Compatibility_v1_0_Commands_Get',
            'mget'                    => 'Predis_Compatibility_v1_0_Commands_GetMultiple',
                'getMultiple'         => 'Predis_Compatibility_v1_0_Commands_GetMultiple',
            'getset'                  => 'Predis_Compatibility_v1_0_Commands_GetSet',
                'getSet'              => 'Predis_Compatibility_v1_0_Commands_GetSet',
            'incr'                    => 'Predis_Compatibility_v1_0_Commands_Increment',
                'increment'           => 'Predis_Compatibility_v1_0_Commands_Increment',
            'incrby'                  => 'Predis_Compatibility_v1_0_Commands_IncrementBy',
                'incrementBy'         => 'Predis_Compatibility_v1_0_Commands_IncrementBy',
            'decr'                    => 'Predis_Compatibility_v1_0_Commands_Decrement',
                'decrement'           => 'Predis_Compatibility_v1_0_Commands_Decrement',
            'decrby'                  => 'Predis_Compatibility_v1_0_Commands_DecrementBy',
                'decrementBy'         => 'Predis_Compatibility_v1_0_Commands_DecrementBy',
            'exists'                  => 'Predis_Compatibility_v1_0_Commands_Exists',
            'del'                     => 'Predis_Compatibility_v1_0_Commands_Delete',
                'delete'              => 'Predis_Compatibility_v1_0_Commands_Delete',
            'type'                    => 'Predis_Compatibility_v1_0_Commands_Type',

            /* commands operating on the key space */
            'keys'               => 'Predis_Compatibility_v1_0_Commands_Keys',
            'randomkey'          => 'Predis_Compatibility_v1_0_Commands_RandomKey',
                'randomKey'      => 'Predis_Compatibility_v1_0_Commands_RandomKey',
            'rename'             => 'Predis_Compatibility_v1_0_Commands_Rename',
            'renamenx'           => 'Predis_Compatibility_v1_0_Commands_RenamePreserve',
                'renamePreserve' => 'Predis_Compatibility_v1_0_Commands_RenamePreserve',
            'expire'             => 'Predis_Compatibility_v1_0_Commands_Expire',
            'expireat'           => 'Predis_Compatibility_v1_0_Commands_ExpireAt',
                'expireAt'       => 'Predis_Compatibility_v1_0_Commands_ExpireAt',
            'dbsize'             => 'Predis_Compatibility_v1_0_Commands_DatabaseSize',
                'databaseSize'   => 'Predis_Compatibility_v1_0_Commands_DatabaseSize',
            'ttl'                => 'Predis_Compatibility_v1_0_Commands_TimeToLive',
                'timeToLive'     => 'Predis_Compatibility_v1_0_Commands_TimeToLive',

            /* commands operating on lists */
            'rpush'            => 'Predis_Compatibility_v1_0_Commands_ListPushTail',
                'pushTail'     => 'Predis_Compatibility_v1_0_Commands_ListPushTail',
            'lpush'            => 'Predis_Compatibility_v1_0_Commands_ListPushHead',
                'pushHead'     => 'Predis_Compatibility_v1_0_Commands_ListPushHead',
            'llen'             => 'Predis_Compatibility_v1_0_Commands_ListLength',
                'listLength'   => 'Predis_Compatibility_v1_0_Commands_ListLength',
            'lrange'           => 'Predis_Compatibility_v1_0_Commands_ListRange',
                'listRange'    => 'Predis_Compatibility_v1_0_Commands_ListRange',
            'ltrim'            => 'Predis_Compatibility_v1_0_Commands_ListTrim',
                'listTrim'     => 'Predis_Compatibility_v1_0_Commands_ListTrim',
            'lindex'           => 'Predis_Compatibility_v1_0_Commands_ListIndex',
                'listIndex'    => 'Predis_Compatibility_v1_0_Commands_ListIndex',
            'lset'             => 'Predis_Compatibility_v1_0_Commands_ListSet',
                'listSet'      => 'Predis_Compatibility_v1_0_Commands_ListSet',
            'lrem'             => 'Predis_Compatibility_v1_0_Commands_ListRemove',
                'listRemove'   => 'Predis_Compatibility_v1_0_Commands_ListRemove',
            'lpop'             => 'Predis_Compatibility_v1_0_Commands_ListPopFirst',
                'popFirst'     => 'Predis_Compatibility_v1_0_Commands_ListPopFirst',
            'rpop'             => 'Predis_Compatibility_v1_0_Commands_ListPopLast',
                'popLast'      => 'Predis_Compatibility_v1_0_Commands_ListPopLast',

            /* commands operating on sets */
            'sadd'                      => 'Predis_Compatibility_v1_0_Commands_SetAdd', 
                'setAdd'                => 'Predis_Compatibility_v1_0_Commands_SetAdd',
            'srem'                      => 'Predis_Compatibility_v1_0_Commands_SetRemove', 
                'setRemove'             => 'Predis_Compatibility_v1_0_Commands_SetRemove',
            'spop'                      => 'Predis_Compatibility_v1_0_Commands_SetPop',
                'setPop'                => 'Predis_Compatibility_v1_0_Commands_SetPop',
            'smove'                     => 'Predis_Compatibility_v1_0_Commands_SetMove', 
                'setMove'               => 'Predis_Compatibility_v1_0_Commands_SetMove',
            'scard'                     => 'Predis_Compatibility_v1_0_Commands_SetCardinality', 
                'setCardinality'        => 'Predis_Compatibility_v1_0_Commands_SetCardinality',
            'sismember'                 => 'Predis_Compatibility_v1_0_Commands_SetIsMember', 
                'setIsMember'           => 'Predis_Compatibility_v1_0_Commands_SetIsMember',
            'sinter'                    => 'Predis_Compatibility_v1_0_Commands_SetIntersection', 
                'setIntersection'       => 'Predis_Compatibility_v1_0_Commands_SetIntersection',
            'sinterstore'               => 'Predis_Compatibility_v1_0_Commands_SetIntersectionStore', 
                'setIntersectionStore'  => 'Predis_Compatibility_v1_0_Commands_SetIntersectionStore',
            'sunion'                    => 'Predis_Compatibility_v1_0_Commands_SetUnion', 
                'setUnion'              => 'Predis_Compatibility_v1_0_Commands_SetUnion',
            'sunionstore'               => 'Predis_Compatibility_v1_0_Commands_SetUnionStore', 
                'setUnionStore'         => 'Predis_Compatibility_v1_0_Commands_SetUnionStore',
            'sdiff'                     => 'Predis_Compatibility_v1_0_Commands_SetDifference', 
                'setDifference'         => 'Predis_Compatibility_v1_0_Commands_SetDifference',
            'sdiffstore'                => 'Predis_Compatibility_v1_0_Commands_SetDifferenceStore', 
                'setDifferenceStore'    => 'Predis_Compatibility_v1_0_Commands_SetDifferenceStore',
            'smembers'                  => 'Predis_Compatibility_v1_0_Commands_SetMembers', 
                'setMembers'            => 'Predis_Compatibility_v1_0_Commands_SetMembers',
            'srandmember'               => 'Predis_Compatibility_v1_0_Commands_SetRandomMember', 
                'setRandomMember'       => 'Predis_Compatibility_v1_0_Commands_SetRandomMember',

            /* multiple databases handling commands */
            'select'                => 'Predis_Compatibility_v1_0_Commands_SelectDatabase', 
                'selectDatabase'    => 'Predis_Compatibility_v1_0_Commands_SelectDatabase',
            'move'                  => 'Predis_Compatibility_v1_0_Commands_MoveKey', 
                'moveKey'           => 'Predis_Compatibility_v1_0_Commands_MoveKey',
            'flushdb'               => 'Predis_Compatibility_v1_0_Commands_FlushDatabase', 
                'flushDatabase'     => 'Predis_Compatibility_v1_0_Commands_FlushDatabase',
            'flushall'              => 'Predis_Compatibility_v1_0_Commands_FlushAll', 
                'flushDatabases'    => 'Predis_Compatibility_v1_0_Commands_FlushAll',

            /* sorting */
            'sort'                  => 'Predis_Compatibility_v1_0_Commands_Sort',

            /* remote server control commands */
            'info'                  => 'Predis_Compatibility_v1_0_Commands_Info',
            'slaveof'               => 'Predis_Compatibility_v1_0_Commands_SlaveOf', 
                'slaveOf'           => 'Predis_Compatibility_v1_0_Commands_SlaveOf',

            /* persistence control commands */
            'save'                  => 'Predis_Compatibility_v1_0_Commands_Save',
            'bgsave'                => 'Predis_Compatibility_v1_0_Commands_BackgroundSave', 
                'backgroundSave'    => 'Predis_Compatibility_v1_0_Commands_BackgroundSave',
            'lastsave'              => 'Predis_Compatibility_v1_0_Commands_LastSave', 
                'lastSave'          => 'Predis_Compatibility_v1_0_Commands_LastSave',
            'shutdown'              => 'Predis_Compatibility_v1_0_Commands_Shutdown',
        );
    }
}

/* ------------------------------------------------------------------------- */

/* miscellaneous commands */
class Predis_Compatibility_v1_0_Commands_Ping extends  Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PING'; }
    public function parseResponse($data) {
        return $data === 'PONG' ? true : false;
    }
}

class Predis_Compatibility_v1_0_Commands_DoEcho extends Predis_BulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'ECHO'; }
}

class Predis_Compatibility_v1_0_Commands_Auth extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'AUTH'; }
}

/* connection handling */
class Predis_Compatibility_v1_0_Commands_Quit extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'QUIT'; }
    public function closesConnection() { return true; }
}

/* commands operating on string values */
class Predis_Compatibility_v1_0_Commands_Set extends Predis_BulkCommand {
    public function getCommandId() { return 'SET'; }
}

class Predis_Compatibility_v1_0_Commands_SetPreserve extends Predis_BulkCommand {
    public function getCommandId() { return 'SETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_Get extends Predis_InlineCommand {
    public function getCommandId() { return 'GET'; }
}

class Predis_Compatibility_v1_0_Commands_GetMultiple extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MGET'; }
}

class Predis_Compatibility_v1_0_Commands_GetSet extends Predis_BulkCommand {
    public function getCommandId() { return 'GETSET'; }
}

class Predis_Compatibility_v1_0_Commands_Increment extends Predis_InlineCommand {
    public function getCommandId() { return 'INCR'; }
}

class Predis_Compatibility_v1_0_Commands_IncrementBy extends Predis_InlineCommand {
    public function getCommandId() { return 'INCRBY'; }
}

class Predis_Compatibility_v1_0_Commands_Decrement extends Predis_InlineCommand {
    public function getCommandId() { return 'DECR'; }
}

class Predis_Compatibility_v1_0_Commands_DecrementBy extends Predis_InlineCommand {
    public function getCommandId() { return 'DECRBY'; }
}

class Predis_Compatibility_v1_0_Commands_Exists extends Predis_InlineCommand {
    public function getCommandId() { return 'EXISTS'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_Delete extends Predis_InlineCommand {
    public function getCommandId() { return 'DEL'; }
}

class Predis_Compatibility_v1_0_Commands_Type extends Predis_InlineCommand {
    public function getCommandId() { return 'TYPE'; }
}

/* commands operating on the key space */
class Predis_Compatibility_v1_0_Commands_Keys extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'KEYS'; }
    public function parseResponse($data) { 
        return strlen($data) > 0 ? explode(' ', $data) : array();
    }
}

class Predis_Compatibility_v1_0_Commands_RandomKey extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RANDOMKEY'; }
    public function parseResponse($data) { return $data !== '' ? $data : null; }
}

class Predis_Compatibility_v1_0_Commands_Rename extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAME'; }
}

class Predis_Compatibility_v1_0_Commands_RenamePreserve extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAMENX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_Expire extends Predis_InlineCommand {
    public function getCommandId() { return 'EXPIRE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_ExpireAt extends Predis_InlineCommand {
    public function getCommandId() { return 'EXPIREAT'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_DatabaseSize extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'DBSIZE'; }
}

class Predis_Compatibility_v1_0_Commands_TimeToLive extends Predis_InlineCommand {
    public function getCommandId() { return 'TTL'; }
}

/* commands operating on lists */
class Predis_Compatibility_v1_0_Commands_ListPushTail extends Predis_BulkCommand {
    public function getCommandId() { return 'RPUSH'; }
}

class Predis_Compatibility_v1_0_Commands_ListPushHead extends Predis_BulkCommand {
    public function getCommandId() { return 'LPUSH'; }
}

class Predis_Compatibility_v1_0_Commands_ListLength extends Predis_InlineCommand {
    public function getCommandId() { return 'LLEN'; }
}

class Predis_Compatibility_v1_0_Commands_ListRange extends Predis_InlineCommand {
    public function getCommandId() { return 'LRANGE'; }
}

class Predis_Compatibility_v1_0_Commands_ListTrim extends Predis_InlineCommand {
    public function getCommandId() { return 'LTRIM'; }
}

class Predis_Compatibility_v1_0_Commands_ListIndex extends Predis_InlineCommand {
    public function getCommandId() { return 'LINDEX'; }
}

class Predis_Compatibility_v1_0_Commands_ListSet extends Predis_BulkCommand {
    public function getCommandId() { return 'LSET'; }
}

class Predis_Compatibility_v1_0_Commands_ListRemove extends Predis_BulkCommand {
    public function getCommandId() { return 'LREM'; }
}

class Predis_Compatibility_v1_0_Commands_ListPopFirst extends Predis_InlineCommand {
    public function getCommandId() { return 'LPOP'; }
}

class Predis_Compatibility_v1_0_Commands_ListPopLast extends Predis_InlineCommand {
    public function getCommandId() { return 'RPOP'; }
}

/* commands operating on sets */
class Predis_Compatibility_v1_0_Commands_SetAdd extends Predis_BulkCommand {
    public function getCommandId() { return 'SADD'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_SetRemove extends Predis_BulkCommand {
    public function getCommandId() { return 'SREM'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_SetPop  extends Predis_InlineCommand {
    public function getCommandId() { return 'SPOP'; }
}

class Predis_Compatibility_v1_0_Commands_SetMove extends Predis_BulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SMOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_SetCardinality extends Predis_InlineCommand {
    public function getCommandId() { return 'SCARD'; }
}

class Predis_Compatibility_v1_0_Commands_SetIsMember extends Predis_BulkCommand {
    public function getCommandId() { return 'SISMEMBER'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_SetIntersection extends Predis_InlineCommand {
    public function getCommandId() { return 'SINTER'; }
}

class Predis_Compatibility_v1_0_Commands_SetIntersectionStore extends Predis_InlineCommand {
    public function getCommandId() { return 'SINTERSTORE'; }
}

class Predis_Compatibility_v1_0_Commands_SetUnion extends Predis_InlineCommand {
    public function getCommandId() { return 'SUNION'; }
}

class Predis_Compatibility_v1_0_Commands_SetUnionStore extends Predis_InlineCommand {
    public function getCommandId() { return 'SUNIONSTORE'; }
}

class Predis_Compatibility_v1_0_Commands_SetDifference extends Predis_InlineCommand {
    public function getCommandId() { return 'SDIFF'; }
}

class Predis_Compatibility_v1_0_Commands_SetDifferenceStore extends Predis_InlineCommand {
    public function getCommandId() { return 'SDIFFSTORE'; }
}

class Predis_Compatibility_v1_0_Commands_SetMembers extends Predis_InlineCommand {
    public function getCommandId() { return 'SMEMBERS'; }
}

class Predis_Compatibility_v1_0_Commands_SetRandomMember extends Predis_InlineCommand {
    public function getCommandId() { return 'SRANDMEMBER'; }
}

/* multiple databases handling commands */
class Predis_Compatibility_v1_0_Commands_SelectDatabase extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SELECT'; }
}

class Predis_Compatibility_v1_0_Commands_MoveKey extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_FlushDatabase extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHDB'; }
}

class Predis_Compatibility_v1_0_Commands_FlushAll extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHALL'; }
}

/* sorting */
class Predis_Compatibility_v1_0_Commands_Sort extends Predis_InlineCommand {
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
class Predis_Compatibility_v1_0_Commands_Save extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SAVE'; }
}

class Predis_Compatibility_v1_0_Commands_BackgroundSave extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'BGSAVE'; }
    public function parseResponse($data) {
        if ($data == 'Background saving started') {
            return true;
        }
        return $data;
    }
}

class Predis_Compatibility_v1_0_Commands_LastSave extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'LASTSAVE'; }
}

class Predis_Compatibility_v1_0_Commands_Shutdown extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SHUTDOWN'; }
    public function closesConnection() { return true; }
}

/* remote server control commands */
class Predis_Compatibility_v1_0_Commands_Info extends Predis_InlineCommand {
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

class Predis_Compatibility_v1_0_Commands_SlaveOf extends Predis_InlineCommand {
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
