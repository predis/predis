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

namespace Predis\Command\Redis;

/**
 * Same as TFCALL command but executes in async mode.
 * @see TFCALL
 *
 * In order to be used in cluster mode
 * @see https://github.com/predis/predis#redis-gears-with-cluster
 */
class TFCALLASYNC extends TFCALL
{
    public function getId()
    {
        return 'TFCALLASYNC';
    }
}
