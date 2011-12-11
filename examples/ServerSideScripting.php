<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require 'SharedConfigurations.php';

// This example will not work with versions of Redis < 2.6.
//
// Additionally to the EVAL command defined in the current development profile, the new
// Predis\Commands\ScriptedCommand base class can be used to build an higher abstraction
// for our "scripted" commands so that they will appear just like any other command on
// the client-side. This is a quick example used to implement INCREX.

use Predis\Commands\ScriptedCommand;

class IncrementExistingKey extends ScriptedCommand
{
    public function getKeysCount()
    {
        return 1;
    }

    public function getScript()
    {
        return
<<<LUA
    local cmd = redis.call
    if cmd('exists', KEYS[1]) == 1 then
        return cmd('incr', KEYS[1])
    end
LUA;
    }
}

$client = new Predis\Client($single_server, 'dev');

$client->getProfile()->defineCommand('increx', 'IncrementExistingKey');

$client->set('foo', 10);

var_dump($client->increx('foo'));       // int(11)
var_dump($client->increx('bar'));       // NULL
