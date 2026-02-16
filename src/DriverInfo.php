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

namespace Predis;

/**
 * Driver information for CLIENT SETINFO.
 *
 * This class holds metadata about upstream libraries using Predis,
 * allowing them to identify themselves to Redis via CLIENT SETINFO commands.
 *
 * The formatted name follows the pattern: "predis(upstream_vVersion)" when an upstream
 * library is specified, or just "predis" when used standalone.
 *
 * LIB-NAME will contain the formatted name (e.g., "predis(laravel_v11.0.0)").
 * LIB-VER will always contain the Predis version (Client::VERSION).
 *
 * Example:
 *   $driverInfo = new DriverInfo();
 *   $driverInfo->addUpstreamDriver('laravel', '11.0.0');
 *   $driverInfo->getFormattedName(); // Returns: "predis(laravel_v11.0.0)"
 *   // LIB-NAME = "predis(laravel_v11.0.0)"
 *   // LIB-VER = Client::VERSION (e.g., "3.4.0")
 *
 * @see https://redis.io/commands/client-setinfo/
 */
class DriverInfo
{
    /** @var string */
    private $name;

    /** @var array<string> */
    private $upstreamDrivers = [];

    /**
     * Create a new DriverInfo instance.
     *
     * @param string $name Base library name (default: 'predis')
     */
    public function __construct(string $name = 'predis')
    {
        $this->name = $name;
    }

    /**
     * Add an upstream driver to this instance.
     *
     * @param  string      $driverName    Upstream library name (e.g., 'laravel', 'symfony')
     * @param  string|null $driverVersion Upstream library version (e.g., '11.0.0', '7.0.0'), optional
     * @return self
     */
    public function addUpstreamDriver(string $driverName, ?string $driverVersion = null): self
    {
        if ($driverVersion !== null) {
            $entry = "{$driverName}_v{$driverVersion}";
        } else {
            $entry = $driverName;
        }
        // Insert at the beginning so latest is first
        array_unshift($this->upstreamDrivers, $entry);

        return $this;
    }

    /**
     * Get the formatted name for CLIENT SETINFO LIB-NAME.
     *
     * Returns the base library name with upstream drivers encoded in the format:
     * - "predis(upstream1_vVersion1;upstream2_vVersion2)" if upstream drivers are set
     * - "predis" if no upstream drivers are set
     *
     * @return string
     */
    public function getFormattedName(): string
    {
        if (empty($this->upstreamDrivers)) {
            return $this->name;
        }

        $upstreamStr = implode(';', $this->upstreamDrivers);

        return "{$this->name}({$upstreamStr})";
    }

    /**
     * Create a default DriverInfo instance for Predis.
     *
     * @return self
     */
    public static function createDefault(): self
    {
        return new self();
    }
}
