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

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-set
 */
class SPOP_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\SPOP';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'SPOP';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = ['key', 2];
        $expected = ['key', 2];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame('member', $this->getCommand()->parseResponse('member'));
    }

    /**
     * @group connected
     */
    public function testPopsRandomMemberFromSet(): void
    {
        $redis = $this->getClient();

        $redis->sadd('letters', 'a', 'b');

        $this->assertContains($redis->spop('letters'), ['a', 'b']);
        $this->assertContains($redis->spop('letters'), ['a', 'b']);

        $this->assertNull($redis->spop('letters'));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 3.2.0
     */
    public function testPopsMoreRandomMembersFromSet(): void
    {
        $redis = $this->getClient();

        $redis->sadd('letters', 'a', 'b', 'c');

        $this->assertSameValues(['a', 'b', 'c'], $redis->spop('letters', 3));
        $this->assertEmpty($redis->spop('letters', 3));

        $this->assertNull($redis->spop('letters'));
    }

    /**
     * @group connected
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->spop('foo');
    }
}
