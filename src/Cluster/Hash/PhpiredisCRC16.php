<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Cluster\Hash;

use Predis\NotSupportedException;

/**
 * Hash generator implementing the CRC-CCITT-16 algorithm used by redis-cluster.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class PhpiredisCRC16 implements HashGeneratorInterface
{
    /**
     *
     */
    public function __construct()
    {
        if (!function_exists('phpiredis_utils_crc16')) {
            // @codeCoverageIgnoreStart
            throw new NotSupportedException(
                'This hash generator requires a compatible version of ext-phpiredis'
            );
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hash($value)
    {
        return phpiredis_utils_crc16($value);
    }
}
