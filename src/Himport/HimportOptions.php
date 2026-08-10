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

namespace Predis\Himport;

/**
 * Value object holding the HIMPORT configuration for a client: the fieldset
 * registry shared across the client (and its derived per-node sub-clients) and
 * the flag controlling whether the himport container automatically re-prepares
 * and retries a fieldset when the server reports "no such fieldset".
 */
class HimportOptions
{
    /**
     * @var FieldsetRegistry
     */
    private $registry;

    /**
     * @var bool
     */
    private $autoPrepare;

    /**
     * @param FieldsetRegistry|null $registry
     * @param bool                  $autoPrepare
     */
    public function __construct(?FieldsetRegistry $registry = null, bool $autoPrepare = true)
    {
        $this->registry = $registry ?: new FieldsetRegistry();
        $this->autoPrepare = $autoPrepare;
    }

    /**
     * @return FieldsetRegistry
     */
    public function getRegistry(): FieldsetRegistry
    {
        return $this->registry;
    }

    /**
     * @return bool
     */
    public function isAutoPrepareEnabled(): bool
    {
        return $this->autoPrepare;
    }
}
