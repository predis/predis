<?php

namespace Predis\Command\Redis\Container\Json;

use Predis\Command\Redis\Container\AbstractContainer;

/**
 * @method array          memory(string $key, string $path = '$')
 * @method array          help()
 */
class JSONDEBUG extends AbstractContainer
{
    protected static $containerId = 'jsondebug';
}
