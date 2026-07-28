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
 * Client-side, connection-agnostic record of the HIMPORT fieldsets an
 * application has declared through the himport container: fieldset name mapped
 * to its ordered field list.
 *
 * This is the canonical source used to (re)build HIMPORT PREPARE commands when a
 * connection that lacks the fieldset needs it (a reconnected socket, a cluster
 * node created after a redirection, a new master after a failover). It records
 * intent, not per-connection state: no "already prepared" flag is kept, because
 * the server's "no such fieldset" error is the only reliable per-connection
 * signal and a client-side flag cannot stay honest across transparent reconnects
 * (e.g. the "relay" extension reconnecting on its own).
 */
class FieldsetRegistry
{
    /**
     * @var array<string, array>
     */
    private $fieldsets = [];

    /**
     * Registers a fieldset, replacing any previous definition (last PREPARE
     * wins, matching the server's silent-replace semantics). Field order is
     * preserved verbatim.
     *
     * @param string $name   Fieldset name.
     * @param array  $fields Ordered field names.
     */
    public function set(string $name, array $fields): void
    {
        $this->fieldsets[$name] = array_values($fields);
    }

    /**
     * @param  string     $name Fieldset name.
     * @return array|null The ordered field list, or null when not registered.
     */
    public function get(string $name): ?array
    {
        return $this->fieldsets[$name] ?? null;
    }

    /**
     * @param  string $name Fieldset name (empty string is a valid name).
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->fieldsets);
    }

    /**
     * @param string $name Fieldset name to forget.
     */
    public function remove(string $name): void
    {
        unset($this->fieldsets[$name]);
    }

    /**
     * Forgets every registered fieldset.
     */
    public function clear(): void
    {
        $this->fieldsets = [];
    }

    /**
     * @return array<string, array>
     */
    public function all(): array
    {
        return $this->fieldsets;
    }
}
