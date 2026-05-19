<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand as RedisCommand;
use UnexpectedValueException;

/**
 * @see http://redis.io/commands/argrep
 */
class ARGREP extends RedisCommand
{
    public const PRED_EXACT = 'EXACT';
    public const PRED_MATCH = 'MATCH';
    public const PRED_GLOB = 'GLOB';
    public const PRED_RE = 'RE';

    public const COMBINATOR_AND = 'AND';
    public const COMBINATOR_OR = 'OR';

    private static $predicates = [
        self::PRED_EXACT, self::PRED_MATCH, self::PRED_GLOB, self::PRED_RE,
    ];

    private static $combinators = [
        self::COMBINATOR_AND, self::COMBINATOR_OR,
    ];

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ARGREP';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        // [key, start, end, predicates, ?combinator, ?limit, ?withValues, ?noCase]
        if (count($arguments) < 4) {
            parent::setArguments($arguments);

            return;
        }

        $processed = [$arguments[0], $arguments[1], $arguments[2]];

        $predicates = $arguments[3] ?? [];

        foreach ($predicates as $pred) {
            if (!is_array($pred) || count($pred) !== 2) {
                throw new UnexpectedValueException(
                    'Each predicate must be a [type, value] pair'
                );
            }

            $type = strtoupper($pred[0]);
            if (!in_array($type, self::$predicates, true)) {
                $allowed = implode(', ', self::$predicates);
                throw new UnexpectedValueException(
                    "Predicate type accepts only: {$allowed} values"
                );
            }

            $processed[] = $type;
            $processed[] = $pred[1];
        }

        if (isset($arguments[4]) && $arguments[4] !== null) {
            $combinator = strtoupper($arguments[4]);
            if (!in_array($combinator, self::$combinators, true)) {
                $allowed = implode(', ', self::$combinators);
                throw new UnexpectedValueException(
                    "Combinator argument accepts only: {$allowed} values"
                );
            }
            $processed[] = $combinator;
        }

        if (isset($arguments[5]) && $arguments[5] !== null) {
            $processed[] = 'LIMIT';
            $processed[] = $arguments[5];
        }

        if (!empty($arguments[6])) {
            $processed[] = 'WITHVALUES';
        }

        if (!empty($arguments[7])) {
            $processed[] = 'NOCASE';
        }

        parent::setArguments($processed);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
