<?php

namespace Predis\Command\Traits;

/**
 * Handles last argument passed into command as WITHSCORES
 */
trait WithScores
{
    public function setArguments(array $arguments)
    {
        $withScores = array_pop($arguments);

        if (is_bool($withScores) && $withScores) {
            $arguments[] = 'WITHSCORES';
        } else if (!is_bool($withScores)) {
            $arguments[] = $withScores;
        }

        parent::setArguments($arguments);
    }
}
