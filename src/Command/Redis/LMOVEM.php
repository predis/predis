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
 * @see https://redis.io/commands/lmovem/
 *
 * Atomically moves multiple elements between two lists. Without a quantifier
 * a single element is moved (like LMOVE, but the reply is still an array).
 * COUNT moves up to count elements, EXACTLY moves exactly count elements or
 * nothing at all. The ordering argument controls how elements are pushed at
 * the destination: OBO pushes one-by-one (block order reversed), BULK
 * preserves the original relative order.
 */
class LMOVEM extends RedisCommand
{
    public const COUNT = 'COUNT';
    public const EXACTLY = 'EXACTLY';
    public const OBO = 'OBO';
    public const BULK = 'BULK';

    public function getId()
    {
        return 'LMOVEM';
    }

    /**
     * Returns the position of the optional quantifier block within the
     * arguments list (BLMOVEM shifts it by one to fit the timeout argument).
     *
     * @return int
     */
    protected function getQuantifierOffset(): int
    {
        return 4;
    }

    public function setArguments(array $arguments)
    {
        $offset = $this->getQuantifierOffset();

        if (count($arguments) <= $offset) {
            parent::setArguments($arguments);

            return;
        }

        $processed = array_slice($arguments, 0, $offset);

        $quantifier = strtoupper($arguments[$offset]);

        if (!in_array($quantifier, [self::COUNT, self::EXACTLY], true)) {
            throw new UnexpectedValueException('Quantifier argument accepts only: COUNT, EXACTLY values');
        }

        if (!isset($arguments[$offset + 1])) {
            throw new UnexpectedValueException("{$quantifier} quantifier requires a count argument");
        }

        if (!isset($arguments[$offset + 2])) {
            throw new UnexpectedValueException("{$quantifier} quantifier requires an ordering argument");
        }

        $ordering = strtoupper($arguments[$offset + 2]);

        if (!in_array($ordering, [self::OBO, self::BULK], true)) {
            throw new UnexpectedValueException('Ordering argument accepts only: OBO, BULK values');
        }

        array_push($processed, $quantifier, $arguments[$offset + 1], $ordering);

        parent::setArguments($processed);
    }

    public function prefixKeys($prefix)
    {
        if ($arguments = $this->getArguments()) {
            $arguments[0] = $prefix . $arguments[0];

            if (isset($arguments[1])) {
                $arguments[1] = $prefix . $arguments[1];
            }

            $this->setRawArguments($arguments);
        }
    }
}
