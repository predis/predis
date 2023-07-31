<?php

namespace Predis\Command\Container;

use Predis\Response\Status;

/**
 * @method Status load(string $libraryCode, bool $replace = false, string $config = null)
 * @method Status delete(string $libraryName)
 * @method array  list(bool $withCode = false, int $verboseLevel = 0, string $libraryName = null)
 */
class TFUNCTION extends AbstractContainer
{
    public function getContainerCommandId(): string
    {
        return 'TFUNCTION';
    }
}
