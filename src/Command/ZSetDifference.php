<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

/**
 * @author Kevin Simard <kev.simard@gmail.com>
 */
class ZSetDifference extends ScriptCommand
{
    /**
     * {@inheritdoc}
     */
    public function getScript()
    {
        return <<<LUA
local command = {'ZUNIONSTORE', 'predis:zdiff:tmp', #ARGV}

for i=1, #ARGV do table.insert(command, ARGV[i]) end

table.insert(command, 'WEIGHTS')

for i=1, #ARGV do
    if i == 1 then table.insert(command, '1')
    else table.insert(command, '0') end
end

table.insert(command, 'AGGREGATE')
table.insert(command, 'MIN')

redis.call(unpack(command))

return redis.call('ZRANGEBYSCORE', 'predis:zdiff:tmp', '1', '+inf')
LUA;
    }
}
