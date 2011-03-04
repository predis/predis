<?php

namespace Predis\Protocols;

use Predis\Commands\ICommand;

class TextCommandSerializer implements ICommandSerializer {
    public function serialize(ICommand $command) {
        $commandId = $command->getId();
        $arguments = $command->getArguments();

        $cmdlen = strlen($commandId);
        $reqlen = count($arguments) + 1;

        $buffer = "*{$reqlen}\r\n\${$cmdlen}\r\n{$commandId}\r\n";
        for ($i = 0; $i < $reqlen - 1; $i++) {
            $argument = $arguments[$i];
            $arglen  = strlen($argument);
            $buffer .= "\${$arglen}\r\n{$argument}\r\n";
        }

        return $buffer;
    }
}
