<?php

namespace Predis\Command\Resolver;

interface CommandResolverInterface
{
    /**
     * Resolves command object from given command ID
     *
     * @param string $commandID Command ID of virtual method call
     * @return string FQDN of corresponding command object
     */
    public function resolve(string $commandID): ?string;
}
