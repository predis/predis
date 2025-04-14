<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Traits\With;

use Predis\Command\Command;

/**
 * Handles last argument passed into command as WITHSCORES.
 *
 * @mixin Command
 */
trait WithScores
{
    public function setArguments(array $arguments)
    {
        $withScores = array_pop($arguments);

        if (is_bool($withScores) && $withScores) {
            $arguments[] = 'WITHSCORES';
        } elseif (!is_bool($withScores)) {
            $arguments[] = $withScores;
        }

        parent::setArguments($arguments);
    }

    /**
     * Checks for the presence of the WITHSCORES modifier.
     *
     * @return bool
     */
    private function isWithScoreModifier(): bool
    {
        $arguments = parent::getArguments();
        $lastArgument = (!empty($arguments)) ? $arguments[count($arguments) - 1] : null;

        return is_string($lastArgument) && strtoupper($lastArgument) === 'WITHSCORES';
    }

    public function parseResponse($data)
    {
        if ($this->isWithScoreModifier()) {
            $result = [];

            for ($i = 0, $iMax = count($data); $i < $iMax; ++$i) {
                if (is_array($data[$i])) {
                    $result[$data[$i][0]] = $data[$i][1]; // Relay
                } elseif (array_key_exists($i + 1, $data)) {
                    $result[$data[$i]] = $data[++$i];
                }
            }

            return $result;
        }

        return $data;
    }
}
