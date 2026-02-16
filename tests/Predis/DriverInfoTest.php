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

use PHPUnit\Framework\TestCase;

class DriverInfoTest extends TestCase
{
    public function testConstructorWithDefaultName(): void
    {
        $driverInfo = new DriverInfo();

        $this->assertSame('predis', $driverInfo->getFormattedName());
    }

    public function testConstructorWithCustomName(): void
    {
        $driverInfo = new DriverInfo('custom-lib');

        $this->assertSame('custom-lib', $driverInfo->getFormattedName());
    }

    public function testAddUpstreamDriverSingle(): void
    {
        $driverInfo = new DriverInfo();
        $driverInfo->addUpstreamDriver('laravel', '11.0.0');

        $this->assertSame('predis(laravel_v11.0.0)', $driverInfo->getFormattedName());
    }

    public function testAddUpstreamDriverWithoutVersion(): void
    {
        $driverInfo = new DriverInfo();
        $driverInfo->addUpstreamDriver('laravel');

        $this->assertSame('predis(laravel)', $driverInfo->getFormattedName());
    }

    public function testAddUpstreamDriverMixedWithAndWithoutVersion(): void
    {
        $driverInfo = new DriverInfo();
        $driverInfo->addUpstreamDriver('laravel', '11.0.0');
        $driverInfo->addUpstreamDriver('my-custom-lib');

        // Latest added appears first
        $this->assertSame('predis(my-custom-lib;laravel_v11.0.0)', $driverInfo->getFormattedName());
    }

    public function testAddUpstreamDriverMultiple(): void
    {
        $driverInfo = new DriverInfo();
        $driverInfo->addUpstreamDriver('laravel', '11.0.0');
        $driverInfo->addUpstreamDriver('symfony', '7.0.0');

        // Latest added appears first
        $this->assertSame('predis(symfony_v7.0.0;laravel_v11.0.0)', $driverInfo->getFormattedName());
    }

    public function testAddUpstreamDriverReturnsThis(): void
    {
        $driverInfo = new DriverInfo();
        $result = $driverInfo->addUpstreamDriver('celery', '5.4.1');

        $this->assertSame($driverInfo, $result);
    }

    public function testAddUpstreamDriverChaining(): void
    {
        $driverInfo = (new DriverInfo())
            ->addUpstreamDriver('laravel', '11.0.0')
            ->addUpstreamDriver('symfony', '7.0.0');

        $this->assertSame('predis(symfony_v7.0.0;laravel_v11.0.0)', $driverInfo->getFormattedName());
    }

    public function testCreateDefault(): void
    {
        $driverInfo = DriverInfo::createDefault();

        $this->assertSame('predis', $driverInfo->getFormattedName());
    }
}
