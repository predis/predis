<?php

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand;

class DELEX extends PrefixableCommand
{
    /**
     * @inheritDoc
     */
    public function getId()
    {
        return 'DELEX';
    }

    /**
     * @inheritDoc
     */
    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
