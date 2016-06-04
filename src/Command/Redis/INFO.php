<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @link http://redis.io/commands/info
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class INFO extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'INFO';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if (empty($data) || !$lines = preg_split('/\r?\n/', $data)) {
            return array();
        }

        if (strpos($lines[0], '#') === 0) {
            return $this->parseNewResponseFormat($lines);
        } else {
            return $this->parseOldResponseFormat($lines);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function parseNewResponseFormat($lines)
    {
        $info = array();
        $current = null;

        foreach ($lines as $row) {
            if ($row === '') {
                continue;
            }

            if (preg_match('/^# (\w+)$/', $row, $matches)) {
                $info[$matches[1]] = array();
                $current = &$info[$matches[1]];
                continue;
            }

            list($k, $v) = $this->parseRow($row);
            $current[$k] = $v;
        }

        return $info;
    }

    /**
     * {@inheritdoc}
     */
    public function parseOldResponseFormat($lines)
    {
        $info = array();

        foreach ($lines as $row) {
            if (strpos($row, ':') === false) {
                continue;
            }

            list($k, $v) = $this->parseRow($row);
            $info[$k] = $v;
        }

        return $info;
    }

    /**
     * Parses a single row of the response and returns the key-value pair.
     *
     * @param string $row Single row of the response.
     *
     * @return array
     */
    protected function parseRow($row)
    {
        list($k, $v) = explode(':', $row, 2);

        if (preg_match('/^db\d+$/', $k)) {
            $v = $this->parseDatabaseStats($v);
        }

        return array($k, $v);
    }

    /**
     * Extracts the statistics of each logical DB from the string buffer.
     *
     * @param string $str Response buffer.
     *
     * @return array
     */
    protected function parseDatabaseStats($str)
    {
        $db = array();

        foreach (explode(',', $str) as $dbvar) {
            list($dbvk, $dbvv) = explode('=', $dbvar);
            $db[trim($dbvk)] = $dbvv;
        }

        return $db;
    }
}
