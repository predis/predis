<?php

namespace Predis\Command\Argument\Server;

use Predis\Command\Argument\ArrayableArgument;

class To implements ArrayableArgument
{
    private const KEYWORD = 'TO';
    private const FORCE_KEYWORD = 'FORCE';

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var bool
     */
    private $isForce;

    public function __construct(string $host, int $port, bool $isForce = false)
    {
        $this->host = $host;
        $this->port = $port;
        $this->isForce = $isForce;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $arguments = [self::KEYWORD, $this->host, $this->port];

        if ($this->isForce) {
            $arguments[] = self::FORCE_KEYWORD;
        }

        return $arguments;
    }
}
