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

class HSetFArgumentsTest extends TestCase
{
    /**
     * @group disconnected
     * @return void
     */
    public function testSetDontCreate(): void
    {
        $this->assertSame(['DC'], (new HSetFArguments())->setDontCreate()->toArray());
    }

    /**
     * @group disconnected
     * @dataProvider fieldModifierProvider
     * @param  string $modifier
     * @return void
     */
    public function testSetFieldModifier(string $modifier): void
    {
        $this->assertSame([strtoupper($modifier)], (new HSetFArguments())->setFieldModifier($modifier)->toArray());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testSetFieldModifierThrowsExceptionOnNonExistingModifier(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Incorrect field modifier value');

        (new HSetFArguments())->setFieldModifier('wrong');
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testSetFieldModifierThrowsExceptionOnAlreadySetModifier(): void
    {
        $test = (new HSetFArguments())->setFieldModifier('dof');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot be mixed with other field modifiers');

        $test->setFieldModifier('dcf');
    }

    /**
     * @group disconnected
     * @dataProvider getModifierProvider
     * @param  string $modifier
     * @return void
     */
    public function testSetGetModifier(string $modifier): void
    {
        $this->assertSame([strtoupper($modifier)], (new HSetFArguments())->setGetModifier($modifier)->toArray());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testSetGetModifierThrowsExceptionOnNonExistingModifier(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Incorrect get modifier value');

        (new HSetFArguments())->setGetModifier('wrong');
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testSetGetModifierThrowsExceptionOnAlreadySetModifier(): void
    {
        $test = (new HSetFArguments())->setGetModifier('getold');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Cannot be mixed with other GET modifiers');

        $test->setGetModifier('getnew');
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testEnableKeepTTL(): void
    {
        $this->assertSame(['KEEPTTL'], (new HSetFArguments())->enableKeepTTL()->toArray());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testEnableKeepTTLThrowsExceptionOnAlreadyExistingTTLModifier(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Keep TTL argument cannot be mixed with one of TTL modifiers');

        (new HSetFArguments())->setTTLModifier('ex', 10)->enableKeepTTL();
    }

    public function fieldModifierProvider(): array
    {
        return [['dof'], ['dcf']];
    }

    public function getModifierProvider(): array
    {
        return [['getnew'], ['getold']];
    }
}
