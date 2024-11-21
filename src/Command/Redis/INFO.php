<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @see http://redis.io/commands/info
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
            return [];
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
        $info = [];
        $current = null;

        foreach ($lines as $row) {
            if ($row === '') {
                continue;
            }

            if (preg_match('/^# (\w+)$/', $row, $matches)) {
                $info[$matches[1]] = [];
                $current = &$info[$matches[1]];
                continue;
            }

            [$k, $v] = $this->parseRow($row);
            $current[$k] = $v;
        }

        return $info;
    }

    /**
     * {@inheritdoc}
     */
    public function parseOldResponseFormat($lines)
    {
        $info = [];

        foreach ($lines as $row) {
            if (strpos($row, ':') === false) {
                continue;
            }

            [$k, $v] = $this->parseRow($row);
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
        if (preg_match('/^module:name/', $row)) {
            return $this->parseModuleRow($row);
        }

        [$k, $v] = explode(':', $row, 2);

        if (preg_match('/^db\d+$/', $k)) {
            $v = $this->parseDatabaseStats($v);
        }

        return [$k, $v];
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
        $db = [];

        foreach (explode(',', $str) as $dbvar) {
            [$dbvk, $dbvv] = explode('=', $dbvar);
            $db[trim($dbvk)] = $dbvv;
        }

        return $db;
    }

    /**
     * Parsing module rows because of different format.
     *
     * @param  string $row
     * @return array
     */
    protected function parseModuleRow(string $row): array
    {
        [$moduleKeyword, $moduleData] = explode(':', $row);
        $explodedData = explode(',', $moduleData);
        $parsedData = [];

        foreach ($explodedData as $moduleDataRow) {
            [$k, $v] = explode('=', $moduleDataRow);

            if ($k === 'name') {
                $parsedData[0] = $v;
                continue;
            }

            $parsedData[1][$k] = $v;
        }

        return $parsedData;
    }
}
