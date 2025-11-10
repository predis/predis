<?php

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand;

class DIGEST extends PrefixableCommand
{
    /**
     * @inheritDoc
     */
    public function getId()
    {
        return 'DIGEST';
    }

    /**
     * @inheritDoc
     */
    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
