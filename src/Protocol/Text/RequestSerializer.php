<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Protocol\Text;

use Predis\Command\CommandInterface;
use Predis\Protocol\RequestSerializerInterface;

/**
 * Request serializer for the standard Redis wire protocol.
 *
 * @see http://redis.io/topics/protocol
 */
class RequestSerializer implements RequestSerializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function serialize(CommandInterface $command)
    {
        $commandID = $command->getId();
        $arguments = $command->getArguments();

        $cmdlen = strlen($commandID);
        $reqlen = count($arguments) + 1;

        $buffer = "*{$reqlen}\r\n\${$cmdlen}\r\n{$commandID}\r\n";

        foreach ($arguments as $argument) {
            $arglen = strlen($argument);
            $buffer .= "\${$arglen}\r\n{$argument}\r\n";
        }

        return $buffer;
    }
}
