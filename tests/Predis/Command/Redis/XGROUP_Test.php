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

use Predis\Response\ServerException;

class XGROUP_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return XGROUP::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'XGROUP';
    }

    /**
     * @dataProvider createArgumentsProvider
     * @group disconnected
     * @param  array $actualArguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testCreateFilterArguments(array $actualArguments, array $expectedResponse): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedResponse, $command->getArguments());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testCreateConsumerFilterArguments(): void
    {
        $arguments = ['CREATECONSUMER', 'key', 'group', 'consumer'];
        $expected = ['CREATECONSUMER', 'key', 'group', 'consumer'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSameValues($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testDelConsumerFilterArguments(): void
    {
        $arguments = ['DELCONSUMER', 'key', 'group', 'consumer'];
        $expected = ['DELCONSUMER', 'key', 'group', 'consumer'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSameValues($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testDestroyFilterArguments(): void
    {
        $arguments = ['DESTROY', 'key', 'group'];
        $expected = ['DESTROY', 'key', 'group'];

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSameValues($expected, $command->getArguments());
    }

    /**
     * @dataProvider setIdArgumentsProvider
     * @group disconnected
     * @param  array $actualArguments
     * @param  array $expectedResponse
     * @return void
     */
    public function testSetIdFilterArguments(array $actualArguments, array $expectedResponse): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedResponse, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $this->assertSame(1, $this->getCommand()->parseResponse(1));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 5.0.0
     */
    public function testCreatesNewConsumerGroupForExistingStream(): void
    {
        $redis = $this->getClient();

        $redis->xadd('key', ['field' => 'value']);

        $this->assertEquals('OK', $redis->xgroup->create('key', 'group', '$'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 5.0.0
     */
    public function testCreateThrowsExceptionOnNonExistingStream(): void
    {
        $redis = $this->getClient();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage(
            'ERR The XGROUP subcommand requires the key to exist.'
        );

        $this->assertEquals('OK', $redis->xgroup->create('key', 'group', '$'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testCreatesNewConsumerForExistingConsumerGroup(): void
    {
        $redis = $this->getClient();

        $redis->xadd('key', ['field' => 'value']);

        $this->assertEquals('OK', $redis->xgroup->create('key', 'group', '$'));
        $this->assertSame(1, $redis->xgroup->createConsumer('key', 'group', 'consumer'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 5.0.0
     */
    public function testRemovesConsumerFromExistingConsumerGroup(): void
    {
        $redis = $this->getClient();

        $redis->xadd('key', ['field' => 'value']);

        $this->assertEquals('OK', $redis->xgroup->create('key', 'group', '$'));
        $this->assertSame(1, $redis->xgroup->createConsumer('key', 'group', 'consumer'));
        $this->assertSame(0, $redis->xgroup->delConsumer('key', 'group', 'consumer'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 5.0.0
     */
    public function testDestroyRemovesGivenConsumerGroup(): void
    {
        $redis = $this->getClient();

        $redis->xadd('key', ['field' => 'value']);

        $this->assertEquals('OK', $redis->xgroup->create('key', 'group', '$'));
        $this->assertEquals(1, $redis->xgroup->destroy('key', 'group'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 5.0.0
     */
    public function testSetGroupLastDeliveredId(): void
    {
        $redis = $this->getClient();

        $redis->xadd('key', ['field' => 'value']);

        $this->assertEquals('OK', $redis->xgroup->create('key', 'group', '$'));
        $this->assertEquals('OK', $redis->xgroup->setId('key', 'group', '0'));
    }

    public function createArgumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['CREATE', 'key', 'group', '$'],
                ['CREATE', 'key', 'group', '$'],
            ],
            'with MKSTREAM modifier' => [
                ['CREATE', 'key', 'group', '$', true],
                ['CREATE', 'key', 'group', '$', 'MKSTREAM'],
            ],
            'with ENTRIESREAD modifier' => [
                ['CREATE', 'key', 'group', '$', false, 'entry'],
                ['CREATE', 'key', 'group', '$', 'ENTRIESREAD', 'entry'],
            ],
            'with all arguments modifier' => [
                ['CREATE', 'key', 'group', '$', true, 'entry'],
                ['CREATE', 'key', 'group', '$', 'MKSTREAM', 'ENTRIESREAD', 'entry'],
            ],
        ];
    }

    public function setIdArgumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['SETID', 'key', 'group', '$'],
                ['SETID', 'key', 'group', '$'],
            ],
            'with ENTRIESREAD modifier' => [
                ['SETID', 'key', 'group', '$', 'entry'],
                ['SETID', 'key', 'group', '$', 'ENTRIESREAD', 'entry'],
            ],
        ];
    }
}
