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

namespace Predis\Command\Argument\Search;

use Predis\Command\Argument\ArrayableArgument;

/**
 * Builds the argument tokens for the COLLECT reducer of FT.AGGREGATE GROUPBY.
 *
 * The produced token list covers only the FIELDS, DISTINCT, SORTBY and LIMIT
 * clauses. The enclosing "REDUCE COLLECT <narg> ... [AS <alias>]" framing is
 * added by AggregateArguments::reduceCollect(), which derives <narg> from the
 * length of this token list.
 *
 * Field and sort-key names are emitted with a leading "@" on the wire; bare
 * names are normalized automatically. Output map keys are the names with the
 * "@" stripped by the server.
 */
class CollectArguments implements ArrayableArgument
{
    public const SORT_ASC = 'ASC';
    public const SORT_DESC = 'DESC';

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * Projects all fields present in the pipeline at the COLLECT stage (FIELDS *).
     *
     * This is stage-local: it projects whatever the pipeline has already
     * materialized, not a whole-document fetch. Pair with an upstream LOAD * to
     * collect the complete document.
     *
     * @return $this
     */
    public function allFields(): self
    {
        array_push($this->arguments, 'FIELDS', '*');

        return $this;
    }

    /**
     * Projects the explicitly listed fields (FIELDS <num_fields> @field ...).
     *
     * Names may be supplied with or without a leading "@"; each is normalized to
     * a single "@<name>" on the wire.
     *
     * @param  string ...$fields
     * @return $this
     */
    public function fields(string ...$fields): self
    {
        $names = array_map([$this, 'ensureAtPrefix'], $fields);

        array_push($this->arguments, 'FIELDS', count($names), ...$names);

        return $this;
    }

    /**
     * Deduplicates entries with identical projected fields.
     *
     * NOTE: DISTINCT is specified by the product but not yet implemented by the
     * server. Sending it currently results in a server error. The option is kept
     * for forward-compatibility; do not rely on it until the server ships it.
     *
     * @return $this
     */
    public function distinct(): self
    {
        $this->arguments[] = 'DISTINCT';

        return $this;
    }

    /**
     * Sorts entries within each group by one or more keys (SORTBY <n> @field [ASC|DESC] ...).
     *
     * Accepts an ordered map of field name to sort direction, e.g.
     * ['@rating' => CollectArguments::SORT_DESC, '@name' => CollectArguments::SORT_ASC].
     * Field names are normalized to carry a leading "@".
     *
     * @param  array $sortByFields Ordered map of field => direction (self::SORT_ASC|self::SORT_DESC)
     * @return $this
     */
    public function sortBy(array $sortByFields): self
    {
        $tokens = [];

        foreach ($sortByFields as $field => $direction) {
            $tokens[] = $this->ensureAtPrefix((string) $field);
            $tokens[] = $direction;
        }

        array_push($this->arguments, 'SORTBY', count($tokens), ...$tokens);

        return $this;
    }

    /**
     * Returns at most <count> entries per group after skipping <offset> (LIMIT <offset> <count>).
     *
     * @param  int   $offset
     * @param  int   $count
     * @return $this
     */
    public function limit(int $offset, int $count): self
    {
        array_push($this->arguments, 'LIMIT', $offset, $count);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->arguments;
    }

    /**
     * Ensures a field or sort-key name carries a single leading "@" prefix.
     *
     * @param  string $name
     * @return string
     */
    private function ensureAtPrefix(string $name): string
    {
        return (strpos($name, '@') === 0) ? $name : '@' . $name;
    }
}
