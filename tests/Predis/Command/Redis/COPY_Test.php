<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

class COPY_Test extends PredisCommandTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExpectedCommand(): string
    {
        return COPY::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedId(): string
    {
        return 'COPY';
    }

    /**
     * @group disconnected
     * @dataProvider argumentsProvider
     */
    public function testFilterArguments(array $actualArguments, array $expectedArguments): void
    {
        $command = $this->getCommand();
        $command->setArguments($actualArguments);

        $this->assertSame($expectedArguments, $command->getArguments());
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
     * @requiresRedisVersion >= 6.2.0
     */
    public function testSuccessfullyCopyValueOnNonExistingDestinationKey(): void
    {
        $redis = $this->getClient();
        $redis->set('key', 'value');

        $actualResponse = $redis->copy('key', 'destination');

        $this->assertSame(1, $actualResponse);
        $this->assertSame($redis->get('key'), $redis->get('destination'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testSuccessfullyCopyValueFromSourceToAnotherDb(): void
    {
        $defaultDatabaseIndexClient = $this->getClient();
        $defaultDatabaseIndexClient->set('key', 'value');

        $copyResponse = $defaultDatabaseIndexClient->copy('key', 'new_key', 14);

        $anotherDatabaseIndexClient = $this->createClient(['database' => 14], null, false);
        $actualValue = $anotherDatabaseIndexClient->get('new_key');
        $anotherDatabaseIndexClient->flushdb();

        $this->assertNull($anotherDatabaseIndexClient->get('new_key'));
        $this->assertSame(1, $copyResponse);
        $this->assertSame('value', $actualValue);
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testDoNotCopyValueOnAlreadyExistingDestinationKey(): void
    {
        $redis = $this->getClient();
        $redis->set('key', 'value');
        $redis->set('destination', 'destination_value');

        $actualResponse = $redis->copy('key', 'destination');

        $this->assertSame(0, $actualResponse);
        $this->assertSame('destination_value', $redis->get('destination'));
    }

    /**
     * @group connected
     * @return void
     * @requiresRedisVersion >= 6.2.0
     */
    public function testSuccessfullyCopyValueWithReplaceArgumentOnAlreadyExistingDestinationKey(): void
    {
        $redis = $this->getClient();
        $redis->set('key', 'value');
        $redis->set('destination', 'destination_value');

        $actualResponse = $redis->copy('key', 'destination', -1, true);

        $this->assertSame(1, $actualResponse);
        $this->assertSame('value', $redis->get('destination'));
    }

    public function argumentsProvider(): array
    {
        return [
            'with default arguments' => [
                ['source', 'destination'],
                ['source', 'destination'],
            ],
            'with DB argument' => [
                ['source', 'destination', 1],
                ['source', 'destination', 'DB', 1],
            ],
            'with replace argument' => [
                ['source', 'destination', -1, true],
                ['source', 'destination', 'REPLACE'],
            ],
            'with all arguments' => [
                ['source', 'destination', 1, true],
                ['source', 'destination', 'DB', 1, 'REPLACE'],
            ],
        ];
    }
}
