.. vim: set ts=3 sw=3 et :
.. php:namespace:: Predis

Lua scripting
-------------

Starting from Redis version 2.6.0, `EVAL`_ and `EVALSHA`_ can be used to
evaluate scripts using the Lua interpreter built into Redis.

Predis provides an abstraction for handling Lua scripts as if them were plain
Redis commands.

Additionally to the ``Command\ServerEval`` provided by Predis, the
``Command\ScriptedCommand`` base class can be used to build a higher abstraction
for our "scripted" commands so that they will appear just like any other command
on the client-side.

Implementing Scripted Commands
==============================

To implement a new scripted command on top of Predis you can start off by
creating a new class that extends the ``Command\ScriptedCommand`` abstract base
class.

This class contains an abstract function ``getScript()`` you must implement in
your command class. Here is where you actually return the Lua script as a
string.

In your class you can also implement the ``getKeysCount()`` function that
specifies the number of arguments that should be considered as keys. The default
behaviour for the base class is to return FALSE to indicate that all the
elements of the arguments array should be considered as keys, but subclasses can
enforce a static number of keys.

Example::

    class IncrementExistingKeysBy extends ScriptedCommand
    {
        public function getKeysCount()
        {
            // Tell Predis to use all the arguments but the last one as arguments
            // for KEYS. The last one will be used to populate ARGV.
            return -1;
        }

        public function getScript()
        {
            return
    <<<LUA
    local cmd, insert = redis.call, table.insert
    local increment, results = ARGV[1], { }

    for idx, key in ipairs(KEYS) do
      if cmd('exists', key) == 1 then
        insert(results, idx, cmd('incrby', key, increment))
      else
        insert(results, idx, false)
      end
    end

    return results
    LUA;
        }
    }

Registering Scripted Commands
=============================

A scripted command should then be registered in the server profile being used by
the client instance and used just like any other client command::

    $client = new Predis\Client();
    $client->getProfile()->defineCommand('increxby', 'IncrementExistingKeysBy');

    $client->mset('foo', 10, 'foobar', 100);
    $client->increxby('foo', 'foofoo', 'foobar', 50);

.. note::
    Internally, scripted commands use `EVALSHA`_ to refer to the Lua script by
    its SHA1 hash in order to save bandwidth, but they are capable of falling
    back to `EVAL`_ when needed.

.. _Lua scripting: http://redis.io/commands/eval
.. _EVALSHA: http://redis.io/commands/evalsha
.. _EVAL: http://redis.io/commands/eval