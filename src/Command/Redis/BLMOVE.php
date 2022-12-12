<?php

namespace Predis\Command\Redis;

class BLMOVE extends LMOVE
{
    public function getId()
    {
        return 'BLMOVE';
    }
}
