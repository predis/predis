<?php

/*
 * This file is used to overRide the SessionHandlerInterface of Predis package and introduce locking to our sessions.
 *
 * @author: Doram Greenblat <doram.greenblat@payfast.co.za>
 */

namespace Predis\Session;
use Predis\ClientInterface;

/**
 * Session handler class that relies on Predis\Client to store PHP's sessions
 * data into one or multiple Redis servers.
 *
 * @author Doram Greenblat <doram.greenblat@payfast.co.za>
 * Extended to facilitate locking using adaptation of the algorithm of the lsw memcached handler located at
 * https://github.com/LeaseWeb/LswMemcacheBundle/blob/master/Session/Storage/LockingSessionHandler.php
 *
 *
 */
class LockingHandler extends \Predis\Session\Handler implements \SessionHandlerInterface
{
    protected $sessionLockId;
    protected $prefix;
    protected $locking;
    protected $locked;
    protected $sessionId;
    protected $lockKey;
    protected $spinLockWait;
    protected $lockMaxWait;

    /**
     * List of available options:
     * @param ClientInterface $client        Fully initialized client instance.
     * @param array           $options       Session handler options.
     */
    public function __construct( ClientInterface $client , array $options = array( ) )
    {
        parent::__construct($client,$options);
        $this->prefix = "session";
        $this->locking = true;
        $this->locked = false;
        $this->lockKey = null;
        $this->spinLockWait = rand(100000,300000);
        $this->lockMaxWait = ini_get('max_execution_time');
    }

    /**
     * close
     * Closes Session and calls session release Lock
     * @access public
     * @author: Doram Greenblat <doram.greenblat@payfast.co.za>
     * @return boolean
     */
    public function close()
    {
        return $this->_unLockSession();
    }

    /**
     * _lockSession
     * Creates a Session Lock
     * Algorithm loosely Based on lsw memcached handler
     * @access private
     * @author: Doram Greenblat <doram.greenblat@payfast.co.za>
     * @return Boolean
     */
    private function _lockSession()
    {
        $attempts = intval( ( 1000000 / $this->spinLockWait ) * $this->lockMaxWait );
        $this->lockKey = $this->sessionId.'.lock';
        $iterations = 0;
        $lockAttained = false;
        while ( ( $iterations < $attempts ) && ( !$lockAttained ) )
        {
            $iterations++;
            if (!$this->_checkLock( ))
            {
                // $this->client->setex($this->prefix.$this->lockKey, $this->lockMaxWait, getmypid());
                // Switched to setnx (ifNot eXist) to prevent race condition where 2 clients see empty lock and set together.
                $this->client->setnx( $this->prefix.$this->lockKey, getmypid() );
                if ( $this->client->get( $this->prefix.$this->lockKey ) == getmypid() )
                {
                    $this->client->expire( $this->prefix.$this->lockKey,$this->lockMaxWait );
                    $this->locked = $lockAttained = true;
                }
            }else{
                usleep( $this->spinLockWait );
            }
        }
        return $lockAttained;
    }

    /**
     * _unLockSession
     * Releases a Session Lock
     * Algorithm loosely Based on lsw memcached handler
     * @access private
     * @author: Doram Greenblat <doram.greenblat@payfast.co.za>
     * @param boolean An Override to force session Unlock, not in use but could be if we ever encounter deadlocks.
     * @return boolean
     */
    private function _unLockSession( $force=false )
    {
        $return = false;
        // Prevent other Users from closing $this session.
        if ( ( $force == true ) || ( ($this->_checkLock()) && ($this->client->get($this->prefix.$this->lockKey) ) == getmypid() ) )
        {
            $this->client->del( $this->prefix.$this->lockKey );
            $this->locked = false;
            $return = true;
        }
        return $return;
    }

    /**
     * _checkLock
     * Checks Status of Lock in Redis DB
     * @access private
     * @author: Doram Greenblat <doram.greenblat@payfast.co.za>
     * @return boolean
     */
    private function _checkLock()
    {
        $return = false;
        $this->lockKey = $this->sessionId.'.lock';
        if ( ( $this->client->exists ( $this->prefix.$this->lockKey ) ) )
        {
            $return = true;
        }
        return $return;
    }


    /**
     * _setSessionId
     * Called to populate sessionId variable if it has not previously been set.
     * This is helpful as php does not always call all our functions with sessionId
     * @access private
     * @author: Doram Greenblat <doram.greenblat@payfast.co.za>
     * @Date: 2016-04-19
     * @param string passed by php
     */
    private function _setSessionId( $sessionId )
    {
        if (!isset($this->sessionId))
        {
            $this->sessionId = $sessionId;
        }
    }

    /**
     * read
     * Called by PHP Session magic.
     * Inspects Lock status and implements then reads and returns data
     * @access public
     * @author: Doram Greenblat <doram.greenblat@payfast.co.za>
     * @param string passed by php
     */
    public function read( $sessionId )
    {
        $return = false;
        $this->_setSessionId( $sessionId );
        if ( $this->_lockSession() )
        {
            $return = $this->client->get( $this->sessionId );
        }
        return $return;
    }


    /**
     * destroy
     * Kills Session record and any associated Lock entry
     * @access public
     * @author: Doram Greenblat <doram.greenblat@payfast.co.za>
     * @return boolean
     */
    public function destroy( $sessionId )
    {
        $this->client->del( $this->sessionId );
        $this->close();
        return true;
    }

}
