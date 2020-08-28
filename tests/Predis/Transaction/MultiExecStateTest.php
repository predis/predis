<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Transaction;

use PredisTestCase;

/**
 * @group realm-transaction
 */
class MultiExecStateTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testFlagsValues(): void
    {
        $this->assertSame(1,  MultiExecState::INITIALIZED);
        $this->assertSame(2,  MultiExecState::INSIDEBLOCK);
        $this->assertSame(4,  MultiExecState::DISCARDED);
        $this->assertSame(8,  MultiExecState::CAS);
        $this->assertSame(16, MultiExecState::WATCH);
    }

    /**
     * @group disconnected
     */
    public function testStateConstructorStartsWithResetState(): void
    {
        $state = new MultiExecState();

        $this->assertSame(0, $state->get());
        $this->assertTrue($state->isReset());
    }

    /**
     * @group disconnected
     */
    public function testCanCheckOneOrMoreStateFlags(): void
    {
        $flags = MultiExecState::INITIALIZED | MultiExecState::CAS;
        $state = new MultiExecState();
        $state->set($flags);

        $this->assertSame($flags, $state->get());

        $this->assertFalse($state->check(MultiExecState::INSIDEBLOCK));
        $this->assertTrue($state->check(MultiExecState::INITIALIZED));
        $this->assertTrue($state->check(MultiExecState::CAS));

        $this->assertTrue($state->check($flags));
        $this->assertFalse($state->check($flags | MultiExecState::INSIDEBLOCK));
    }

    /**
     * @group disconnected
     */
    public function testSettingAndGettingWholeFlags(): void
    {
        $flags = MultiExecState::INITIALIZED | MultiExecState::CAS;
        $state = new MultiExecState();
        $state->set($flags);

        $this->assertFalse($state->check(MultiExecState::INSIDEBLOCK));
        $this->assertTrue($state->check(MultiExecState::INITIALIZED));
        $this->assertTrue($state->check(MultiExecState::CAS));
        $this->assertSame($flags, $state->get());
    }

    /**
     * @group disconnected
     */
    public function testCanFlagSingleStates(): void
    {
        $flags = MultiExecState::INITIALIZED | MultiExecState::CAS;
        $state = new MultiExecState();

        $state->flag(MultiExecState::INITIALIZED);
        $this->assertTrue($state->check(MultiExecState::INITIALIZED));
        $this->assertFalse($state->check(MultiExecState::CAS));

        $state->flag(MultiExecState::CAS);
        $this->assertTrue($state->check(MultiExecState::INITIALIZED));
        $this->assertTrue($state->check(MultiExecState::CAS));

        $this->assertSame($flags, $state->get());
    }

    /**
     * @group disconnected
     */
    public function testCanUnflagSingleStates(): void
    {
        $state = new MultiExecState();
        $state->set(MultiExecState::INITIALIZED | MultiExecState::CAS);

        $this->assertTrue($state->check(MultiExecState::INITIALIZED));
        $this->assertTrue($state->check(MultiExecState::CAS));

        $state->unflag(MultiExecState::CAS);
        $this->assertTrue($state->check(MultiExecState::INITIALIZED));
        $this->assertFalse($state->check(MultiExecState::CAS));

        $state->unflag(MultiExecState::INITIALIZED);
        $this->assertFalse($state->check(MultiExecState::INITIALIZED));
        $this->assertFalse($state->check(MultiExecState::CAS));

        $this->assertTrue($state->isReset());
    }

    /**
     * @group disconnected
     */
    public function testIsInitializedMethod(): void
    {
        $state = new MultiExecState();

        $this->assertFalse($state->isInitialized());

        $state->set(MultiExecState::INITIALIZED);
        $this->assertTrue($state->isInitialized());
    }

    /**
     * @group disconnected
     */
    public function testIsExecuting(): void
    {
        $state = new MultiExecState();

        $this->assertFalse($state->isExecuting());

        $state->set(MultiExecState::INSIDEBLOCK);
        $this->assertTrue($state->isExecuting());
    }

    /**
     * @group disconnected
     */
    public function testIsCAS(): void
    {
        $state = new MultiExecState();

        $this->assertFalse($state->isCAS());

        $state->set(MultiExecState::CAS);
        $this->assertTrue($state->isCAS());
    }

    /**
     * @group disconnected
     */
    public function testIsWatchAllowed(): void
    {
        $state = new MultiExecState();

        $this->assertFalse($state->isWatchAllowed());

        $state->flag(MultiExecState::INITIALIZED);
        $this->assertTrue($state->isWatchAllowed());

        $state->flag(MultiExecState::CAS);
        $this->assertFalse($state->isWatchAllowed());

        $state->unflag(MultiExecState::CAS);
        $this->assertTrue($state->isWatchAllowed());
    }

    /**
     * @group disconnected
     */
    public function testIsWatching(): void
    {
        $state = new MultiExecState();

        $this->assertFalse($state->isWatching());

        $state->set(MultiExecState::WATCH);
        $this->assertTrue($state->isWatching());
    }

    /**
     * @group disconnected
     */
    public function testIsDiscarded(): void
    {
        $state = new MultiExecState();

        $this->assertFalse($state->isDiscarded());

        $state->set(MultiExecState::DISCARDED);
        $this->assertTrue($state->isDiscarded());
    }
}
