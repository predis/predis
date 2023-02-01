<?php

namespace Predis\Command\Redis\CountMinSketch;

use Predis\Command\Command as RedisCommand;

/**
 * @see https://redis.io/commands/cms.info/
 *
 * Returns width, depth and total count of the sketch.
 */
class CMSINFO extends RedisCommand
{
    public function getId()
    {
        return 'CMS.INFO';
    }

    public function parseResponse($data)
    {
        if (count($data) > 1) {
            $result = [];

            for ($i = 0, $iMax = count($data); $i < $iMax; ++$i) {
                if (array_key_exists($i + 1, $data)) {
                    $result[(string) $data[$i]] = $data[++$i];
                }
            }

            return $result;
        }

        return $data;
    }
}
