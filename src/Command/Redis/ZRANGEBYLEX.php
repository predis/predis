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
 * @link http://redis.io/commands/zrangebylex
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZRANGEBYLEX extends ZRANGE
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZRANGEBYLEX';
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareOptions($options)
    {
        $opts = array_change_key_case($options, CASE_UPPER);
        $finalizedOpts = array();

        if (isset($opts['LIMIT']) && is_array($opts['LIMIT'])) {
            $limit = array_change_key_case($opts['LIMIT'], CASE_UPPER);

            $finalizedOpts[] = 'LIMIT';
            $finalizedOpts[] = isset($limit['OFFSET']) ? $limit['OFFSET'] : $limit[0];
            $finalizedOpts[] = isset($limit['COUNT']) ? $limit['COUNT'] : $limit[1];
        }

        return $finalizedOpts;
    }

    /**
     * {@inheritdoc}
     */
    protected function withScores()
    {
        return false;
    }
}
