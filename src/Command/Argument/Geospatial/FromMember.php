<?php

namespace Predis\Command\Argument\Geospatial;

class FromMember implements FromInterface
{
    private const KEYWORD = 'FROMMEMBER';

    /**
     * @var string
     */
    private $member;

    public function __construct(string $member)
    {
        $this->member = $member;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [self::KEYWORD, $this->member];
    }
}
