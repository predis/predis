<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Argument\Hash;

use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class HGetFArgumentsTest extends TestCase
{
    /**
     * @group disconnected
     * @return void
     */
    public function testSetPersist(): void
    {
        $this->assertSame(['PERSIST'], (new HGetFArguments())->setPersist()->toArray());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testSetPersistThrowsExceptionOnAlreadyExistingTTLModifier(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('PERSIST argument cannot be mixed with one of TTL modifiers');

        (new HGetFArguments())->setTTLModifier('ex', 10)->setPersist();
    }
}
